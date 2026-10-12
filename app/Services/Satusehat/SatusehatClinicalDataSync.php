<?php

namespace App\Services\Satusehat;

use App\Models\AllergyIntolerance;
use App\Models\AnamnesisCondition;
use App\Models\Appointment;
use App\Models\CareGoal;
use App\Models\CarePlan;
use App\Models\ClinicalImpression;
use App\Models\FamilyMemberHistory;
use App\Models\MedicationStatement;
use App\Models\Patient;
use App\Models\SatusehatObservation;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Throwable;

class SatusehatClinicalDataSync
{
    public function syncPatientData(Patient $patient, SatusehatClient $client): bool
    {
        $patient->loadMissing(['familyMemberHistories', 'allergyIntolerances', 'medicationStatements']);
        $synced = false;

        foreach ($patient->familyMemberHistories as $record) {
            $this->syncRecord($client, $record, 'FamilyMemberHistory', $this->familyMemberHistoryPayload($record, $patient));
            $synced = true;
        }

        foreach ($patient->allergyIntolerances as $record) {
            $this->syncRecord($client, $record, 'AllergyIntolerance', $this->allergyPayload($record, $patient));
            $synced = true;
        }

        foreach ($patient->medicationStatements as $record) {
            $this->syncRecord($client, $record, 'MedicationStatement', $this->medicationStatementPayload($record, $patient));
            $synced = true;
        }

        return $synced;
    }

    public function syncAppointmentData(Appointment $appointment, SatusehatClient $client): bool
    {
        $appointment->loadMissing([
            'patient',
            'clinic_doctor.user',
            'clinic_doctor.clinic',
            'anamnesisConditions',
            'satusehatObservations',
            'clinicalImpressions',
            'careGoals',
            'carePlans',
        ]);

        $patient = $appointment->patient;
        $clinic = $appointment->clinic_doctor?->clinic;
        $doctor = $appointment->clinic_doctor?->user;

        if (!$patient?->satusehat_patient_id || !$appointment->satusehat_encounter_id || !$clinic?->satusehat_organization_id) {
            throw new RuntimeException('Patient, Encounter, dan Organization ID harus tersedia sebelum data klinis tambahan dikirim.');
        }

        $synced = false;

        foreach ($appointment->anamnesisConditions as $record) {
            if ($record->code === $appointment->satusehat_diagnosis_code && $appointment->satusehat_condition_id) {
                $record->forceFill([
                    'satusehat_id' => $appointment->satusehat_condition_id,
                    'satusehat_sync_status' => 'linked',
                    'satusehat_sync_error' => null,
                    'satusehat_synced_at' => now(),
                ])->saveQuietly();

                continue;
            }

            $this->syncRecord($client, $record, 'Condition', $this->anamnesisConditionPayload($record, $appointment, $patient));
            $synced = true;
        }

        $this->ensureVitalObservations($appointment);
        $appointment->load('satusehatObservations');

        foreach ($appointment->satusehatObservations as $record) {
            $this->syncRecord(
                $client,
                $record,
                'Observation',
                $this->observationPayload($record, $appointment, $patient, $doctor),
            );
            $synced = true;
        }

        foreach ($appointment->clinicalImpressions as $record) {
            $this->syncRecord(
                $client,
                $record,
                'ClinicalImpression',
                $this->clinicalImpressionPayload($record, $appointment, $patient, $doctor),
            );
            $synced = true;
        }

        foreach ($appointment->careGoals as $record) {
            $this->syncRecord($client, $record, 'Goal', $this->goalPayload($record, $appointment, $patient));
            $synced = true;
        }

        foreach ($appointment->carePlans as $record) {
            $this->syncRecord($client, $record, 'CarePlan', $this->carePlanPayload($record, $appointment, $patient, $doctor));
            $synced = true;
        }

        return $synced;
    }

    private function anamnesisConditionPayload(AnamnesisCondition $record, Appointment $appointment, Patient $patient): array
    {
        $payload = [
            'resourceType' => 'Condition',
            'meta' => ['profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/Condition']],
            'clinicalStatus' => ['coding' => [[
                'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                'code' => $record->clinical_status,
            ]]],
            'verificationStatus' => ['coding' => [[
                'system' => 'http://terminology.hl7.org/CodeSystem/condition-ver-status',
                'code' => $record->verification_status,
            ]]],
            'code' => ['coding' => [[
                'system' => 'http://hl7.org/fhir/sid/icd-10',
                'code' => $record->code,
                'display' => $record->display,
            ]]],
            'subject' => ['reference' => 'Patient/' . $patient->satusehat_patient_id],
            'encounter' => ['reference' => 'Encounter/' . $appointment->satusehat_encounter_id],
        ];

        if ($record->onset_date) {
            $payload['onsetDateTime'] = $record->onset_date->toDateString();
        }
        if ($record->notes) {
            $payload['note'] = [['text' => $record->notes]];
        }

        return $payload;
    }

    private function ensureVitalObservations(Appointment $appointment): void
    {
        $measurements = [
            ['code' => '8302-2', 'display' => 'Body height', 'value' => $appointment->height, 'unit' => 'cm', 'unit_code' => 'cm'],
            ['code' => '29463-7', 'display' => 'Body weight', 'value' => $appointment->weight, 'unit' => 'kg', 'unit_code' => 'kg'],
        ];

        if (!preg_match('/^\s*(\d+(?:\.\d+)?)\s*\/\s*(\d+(?:\.\d+)?)/', (string) $appointment->blood_pressure, $bloodPressure)) {
            throw new RuntimeException('Tekanan darah harus menggunakan format sistolik/diastolik, misalnya 120/80.');
        }

        $measurements[] = ['code' => '8480-6', 'display' => 'Systolic blood pressure', 'value' => $bloodPressure[1], 'unit' => 'mmHg', 'unit_code' => 'mm[Hg]'];
        $measurements[] = ['code' => '8462-4', 'display' => 'Diastolic blood pressure', 'value' => $bloodPressure[2], 'unit' => 'mmHg', 'unit_code' => 'mm[Hg]'];

        foreach ($measurements as $measurement) {
            if (!is_numeric($measurement['value']) || (float) $measurement['value'] <= 0) {
                throw new RuntimeException("Nilai {$measurement['display']} harus lebih besar dari nol.");
            }

            $observation = $appointment->satusehatObservations()->firstOrNew([
                'code_system' => 'http://loinc.org',
                'code' => $measurement['code'],
            ]);
            $observation->fill([
                'category' => 'vital-signs',
                'display' => $measurement['display'],
                'value_type' => 'quantity',
                'value_quantity' => $measurement['value'],
                'unit' => $measurement['unit'],
                'unit_code' => $measurement['unit_code'],
            ])->save();
        }
    }

    private function familyMemberHistoryPayload(FamilyMemberHistory $record, Patient $patient): array
    {
        $payload = [
            'resourceType' => 'FamilyMemberHistory',
            'meta' => ['profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/FamilyMemberHistory']],
            'status' => $record->status,
            'patient' => ['reference' => 'Patient/' . $patient->satusehat_patient_id],
            'relationship' => ['coding' => [[
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-RoleCode',
                'code' => $record->relationship_code,
                'display' => $record->relationship_display,
            ]]],
            'condition' => [[
                'code' => ['coding' => [[
                    'system' => 'http://hl7.org/fhir/sid/icd-10',
                    'code' => $record->condition_code,
                    'display' => $record->condition_display,
                ]]],
            ]],
        ];

        if ($record->onset_age) {
            $payload['condition'][0]['onsetString'] = $record->onset_age;
        }
        if ($record->notes) {
            $payload['note'] = [['text' => $record->notes]];
        }

        return $payload;
    }

    private function allergyPayload(AllergyIntolerance $record, Patient $patient): array
    {
        $payload = [
            'resourceType' => 'AllergyIntolerance',
            'meta' => ['profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/AllergyIntolerance']],
            'clinicalStatus' => ['coding' => [[
                'system' => 'http://terminology.hl7.org/CodeSystem/allergyintolerance-clinical',
                'code' => $record->clinical_status,
            ]]],
            'verificationStatus' => ['coding' => [[
                'system' => 'http://terminology.hl7.org/CodeSystem/allergyintolerance-verification',
                'code' => $record->verification_status,
            ]]],
            'category' => [$record->category],
            'code' => ['coding' => [[
                'system' => $record->code_system,
                'code' => $record->code,
                'display' => $record->display,
            ]]],
            'patient' => ['reference' => 'Patient/' . $patient->satusehat_patient_id],
        ];

        if ($record->criticality) {
            $payload['criticality'] = $record->criticality;
        }
        if ($record->onset_date) {
            $payload['onsetDateTime'] = $record->onset_date->toDateString();
        }
        if ($record->reaction_code || $record->reaction_description) {
            if (!$record->reaction_code) {
                throw new RuntimeException("Alergi {$record->display} memerlukan kode manifestation SNOMED CT.");
            }

            $reaction = [];
            $reaction['manifestation'] = [['coding' => [[
                'system' => $record->reaction_code_system,
                'code' => $record->reaction_code,
                'display' => $record->reaction_display,
            ]]]];
            if ($record->reaction_description) {
                $reaction['description'] = $record->reaction_description;
            }
            $payload['reaction'] = [$reaction];
        }
        if ($record->notes) {
            $payload['note'] = [['text' => $record->notes]];
        }

        return $payload;
    }

    private function medicationStatementPayload(MedicationStatement $record, Patient $patient): array
    {
        $payload = [
            'resourceType' => 'MedicationStatement',
            'meta' => ['profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationStatement']],
            'status' => $record->status,
            'medicationCodeableConcept' => ['coding' => [[
                'system' => $record->medication_system,
                'code' => $record->medication_code,
                'display' => $record->medication_display,
            ]]],
            'subject' => ['reference' => 'Patient/' . $patient->satusehat_patient_id],
        ];

        if ($record->effective_start && $record->effective_end) {
            $payload['effectivePeriod'] = [
                'start' => $record->effective_start->toDateString(),
                'end' => $record->effective_end->toDateString(),
            ];
        } elseif ($record->effective_start) {
            $payload['effectiveDateTime'] = $record->effective_start->toDateString();
        }
        if ($record->dosage) {
            $payload['dosage'] = [['text' => $record->dosage]];
        }
        if ($record->notes) {
            $payload['note'] = [['text' => $record->notes]];
        }

        return $payload;
    }

    private function observationPayload(SatusehatObservation $record, Appointment $appointment, Patient $patient, ?object $doctor): array
    {
        $category = $record->category === 'functional' ? 'survey' : $record->category;
        $payload = [
            'resourceType' => 'Observation',
            'meta' => ['profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/Observation']],
            'status' => 'final',
            'category' => [['coding' => [[
                'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                'code' => $category,
            ]]]],
            'code' => ['coding' => [[
                'system' => $record->code_system,
                'code' => $record->code,
                'display' => $record->display,
            ]]],
            'subject' => ['reference' => 'Patient/' . $patient->satusehat_patient_id],
            'encounter' => ['reference' => 'Encounter/' . $appointment->satusehat_encounter_id],
            'effectiveDateTime' => $appointment->schedule_date->toIso8601String(),
            'issued' => now()->utc()->toIso8601String(),
        ];

        if ($record->value_type === 'quantity') {
            if ($record->value_quantity === null || !$record->unit || !$record->unit_code) {
                throw new RuntimeException("Observation {$record->display} memerlukan nilai dan satuan UCUM.");
            }
            $payload['valueQuantity'] = [
                'value' => (float) $record->value_quantity,
                'unit' => $record->unit,
                'system' => 'http://unitsofmeasure.org',
                'code' => $record->unit_code,
            ];
        } elseif ($record->value_string) {
            $payload['valueString'] = $record->value_string;
        } else {
            throw new RuntimeException("Observation {$record->display} memerlukan hasil pemeriksaan.");
        }

        if ($doctor?->satusehat_practitioner_id) {
            $payload['performer'] = [['reference' => 'Practitioner/' . $doctor->satusehat_practitioner_id]];
        }
        if ($record->notes) {
            $payload['note'] = [['text' => $record->notes]];
        }

        return $payload;
    }

    private function clinicalImpressionPayload(ClinicalImpression $record, Appointment $appointment, Patient $patient, ?object $doctor): array
    {
        if (!filled($record->investigation_code)) {
            throw new RuntimeException('ClinicalImpression memerlukan kode investigasi SNOMED CT.');
        }

        $payload = [
            'resourceType' => 'ClinicalImpression',
            'meta' => ['profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/ClinicalImpression']],
            'status' => $record->status,
            'subject' => ['reference' => 'Patient/' . $patient->satusehat_patient_id],
            'encounter' => ['reference' => 'Encounter/' . $appointment->satusehat_encounter_id],
            'investigation' => [[
                'code' => [
                    'coding' => [[
                        'system' => $record->investigation_system,
                        'code' => $record->investigation_code,
                        'display' => $record->investigation_display,
                    ]],
                    'text' => $record->investigation ?: $record->investigation_display,
                ],
            ]],
            'summary' => $record->summary,
            'prognosisCodeableConcept' => [[
                'coding' => [[
                    'system' => $record->prognosis_system,
                    'code' => $record->prognosis_code,
                    'display' => $record->prognosis_display,
                ]],
            ]],
        ];

        $description = $record->investigation ?: $this->soapNotes($appointment);
        if ($description) {
            $payload['description'] = $description;
        }

        if ($doctor?->satusehat_practitioner_id) {
            $payload['assessor'] = ['reference' => 'Practitioner/' . $doctor->satusehat_practitioner_id];
        }
        if ($soapNotes = $this->soapNotes($appointment)) {
            $payload['note'] = [['text' => $soapNotes]];
        }

        return $payload;
    }

    private function goalPayload(CareGoal $record, Appointment $appointment, Patient $patient): array
    {
        $payload = [
            'resourceType' => 'Goal',
            'meta' => ['profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/Goal']],
            'lifecycleStatus' => $record->lifecycle_status,
            'description' => ['coding' => [[
                'system' => $record->description_system,
                'code' => $record->description_code,
                'display' => $record->description_display,
            ]]],
            'subject' => ['reference' => 'Patient/' . $patient->satusehat_patient_id],
        ];

        if ($record->start_date) {
            $payload['startDate'] = $record->start_date->toDateString();
        }
        if ($record->target_detail || $record->target_due_date) {
            $target = [];
            if ($record->target_detail) {
                $target['detailString'] = $record->target_detail;
            }
            if ($record->target_due_date) {
                $target['dueDate'] = $record->target_due_date->toDateString();
            }
            $payload['target'] = [$target];
        }
        if ($record->notes) {
            $payload['note'] = [['text' => $record->notes]];
        }

        return $payload;
    }

    private function carePlanPayload(CarePlan $record, Appointment $appointment, Patient $patient, ?object $doctor): array
    {
        $category = match ($record->plan_type) {
            'medication_instruction' => ['code' => '736378000', 'display' => 'Medication management plan'],
            'nursing_instruction' => ['code' => '773130005', 'display' => 'Nursing care plan'],
            default => ['code' => '734163000', 'display' => 'Care plan'],
        };

        $payload = [
            'resourceType' => 'CarePlan',
            'meta' => ['profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/CarePlan']],
            'status' => $record->status,
            'intent' => $record->intent,
            'category' => [['coding' => [[
                'system' => 'http://snomed.info/sct',
                'code' => $category['code'],
                'display' => $category['display'],
            ]]]],
            'title' => $record->title,
            'description' => $record->description ?: ($appointment->plan ?: $record->activity_description),
            'subject' => ['reference' => 'Patient/' . $patient->satusehat_patient_id],
            'encounter' => ['reference' => 'Encounter/' . $appointment->satusehat_encounter_id],
            'activity' => [[
                'detail' => [
                    'kind' => 'Task',
                    'status' => $record->activity_status,
                    'description' => $record->activity_description,
                ],
            ]],
        ];

        $goalReferences = $appointment->careGoals
            ->filter(fn (CareGoal $goal): bool => filled($goal->satusehat_id))
            ->map(fn (CareGoal $goal): array => ['reference' => 'Goal/' . $goal->satusehat_id])
            ->values()
            ->all();

        if ($goalReferences !== []) {
            $payload['goal'] = $goalReferences;
        }
        if ($appointment->satusehat_condition_id) {
            $payload['addresses'] = [[
                'reference' => 'Condition/' . $appointment->satusehat_condition_id,
            ]];
        }

        if ($doctor?->satusehat_practitioner_id) {
            $payload['author'] = ['reference' => 'Practitioner/' . $doctor->satusehat_practitioner_id];
        }
        if ($record->scheduled_string) {
            $payload['activity'][0]['detail']['scheduledString'] = $record->scheduled_string;
        }
        $carePlanNotes = collect([$appointment->plan, $record->notes])
            ->filter()
            ->implode("\n");
        if ($carePlanNotes !== '') {
            $payload['note'] = [['text' => $carePlanNotes]];
        }

        return $payload;
    }

    private function soapNotes(Appointment $appointment): ?string
    {
        $sections = [
            'S' => $appointment->symptoms,
            'O' => $appointment->objective,
            'A' => $appointment->diagnostic,
            'P' => $appointment->plan,
        ];

        $notes = collect($sections)
            ->filter(fn ($value): bool => filled($value))
            ->map(fn ($value, $section): string => $section . ': ' . $value)
            ->implode("\n");

        return $notes !== '' ? $notes : null;
    }

    private function syncRecord(SatusehatClient $client, Model $record, string $resourceType, array $payload): void
    {
        $record->forceFill([
            'satusehat_sync_status' => 'syncing',
            'satusehat_sync_error' => null,
        ])->saveQuietly();

        try {
            $resourceId = $record->satusehat_id;
            $response = $resourceId
                ? $client->update($resourceType, $resourceId, [...$payload, 'id' => $resourceId])
                : $client->create($resourceType, $payload);

            $resourceId ??= $response['id'] ?? null;
            if (!filled($resourceId)) {
                throw new RuntimeException("SATUSEHAT tidak mengembalikan ID {$resourceType}.");
            }

            $record->forceFill([
                'satusehat_id' => $resourceId,
                'satusehat_sync_status' => 'synced',
                'satusehat_sync_error' => null,
                'satusehat_synced_at' => now(),
            ])->saveQuietly();
        } catch (Throwable $exception) {
            $record->forceFill([
                'satusehat_sync_status' => 'failed',
                'satusehat_sync_error' => mb_substr($exception->getMessage(), 0, 60000),
            ])->saveQuietly();

            throw $exception;
        }
    }
}
