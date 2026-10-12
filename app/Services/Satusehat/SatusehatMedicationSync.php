<?php

namespace App\Services\Satusehat;

use App\Models\Prescription;
use Illuminate\Support\Str;
use RuntimeException;

class SatusehatMedicationSync
{
    public function syncRequest(Prescription $prescription, SatusehatClient $client): void
    {
        $this->sync($prescription, $client, false);
    }

    public function syncDispense(Prescription $prescription, SatusehatClient $client): void
    {
        $this->sync($prescription, $client, true);
    }

    public function cancelRequest(Prescription $prescription, SatusehatClient $client): void
    {
        $this->sync($prescription, $client, false, true);
    }

    private function sync(Prescription $prescription, SatusehatClient $client, bool $dispense, bool $cancelled = false): void
    {
        $prescription->loadMissing(['drug', 'dispenser', 'appointment.patient', 'appointment.clinic_doctor.user', 'appointment.clinic_doctor.clinic']);
        $drug = $prescription->drug;
        $appointment = $prescription->appointment;
        $clinic = $appointment?->clinic_doctor?->clinic;
        $patient = $appointment?->patient;
        $doctor = $appointment?->clinic_doctor?->user;
        $kfaCode = $dispense ? ($prescription->dispensed_kfa_code ?: $drug?->kfa_code) : $drug?->kfa_code;
        $kfaDisplay = $dispense ? ($prescription->dispensed_kfa_display ?: $drug?->kfa_display) : $drug?->kfa_display;

        if (!$kfaCode || !$kfaDisplay) throw new RuntimeException('Lengkapi kode dan nama KFA pada data obat sebelum sinkronisasi.');
        if ($dispense && !preg_match('/^93[0-9]{6}$/', $kfaCode)) throw new RuntimeException('MedicationDispense harus memakai KFA produk obat aktual (kode 93xxxxxx).');
        if (!$dispense && !preg_match('/^(92|93)[0-9]{6}$/', $kfaCode)) throw new RuntimeException('MedicationRequest harus memakai KFA produk template (92xxxxxx) atau produk aktual (93xxxxxx).');

        foreach (['ID Organization klinik' => $clinic?->satusehat_organization_id, 'IHS Patient ID' => $patient?->satusehat_patient_id] as $label => $value) {
            if (!filled($value)) throw new RuntimeException("{$label} belum tersedia.");
        }
        if (!$doctor?->satusehat_practitioner_id) throw new RuntimeException('IHS Practitioner ID dokter belum tersedia.');
        if ($dispense && !$prescription->dispensed_by) throw new RuntimeException('Petugas apotik belum tercatat pada penyerahan.');
        if ($dispense && !$appointment->satusehat_encounter_id) throw new RuntimeException('Encounter SATUSEHAT appointment belum tersedia. Sinkronkan appointment sebelum penyerahan obat.');
        if ($dispense && !$prescription->satusehat_medication_request_id) throw new RuntimeException('MedicationRequest belum dikirim ke SATUSEHAT.');
        if (!$prescription->dose_value || !$prescription->dose_unit || !$prescription->frequency || !$prescription->route) {
            throw new RuntimeException('Lengkapi dosis, satuan dosis, frekuensi, dan rute pemberian pada resep.');
        }

        $resourceType = $dispense ? 'MedicationDispense' : 'MedicationRequest';
        $remoteId = $dispense ? $prescription->satusehat_medication_dispense_id : $prescription->satusehat_medication_request_id;
        $medicationId = $drug->satusehat_medication_id;
        $fhirUrl = rtrim(config('satusehat.fhir_url') ?: config('satusehat.base_url') . '/fhir-r4/v1', '/');
        $medicationFullUrl = $medicationId ? $fhirUrl . '/Medication/' . $medicationId : 'urn:uuid:' . Str::uuid();
        $recordFullUrl = $remoteId ? $fhirUrl . '/' . $resourceType . '/' . $remoteId : 'urn:uuid:' . Str::uuid();
        $medicationReference = $medicationId ? 'Medication/' . $medicationId : $medicationFullUrl;
        $medication = [
            'resourceType' => 'Medication',
            'meta' => ['profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/Medication']],
            'identifier' => [[
                'use' => 'official',
                'system' => 'http://sys-ids.kemkes.go.id/medication/' . $clinic->satusehat_organization_id,
                'value' => (string) $drug->getKey(),
            ]],
            'code' => ['coding' => [[
                'system' => 'http://sys-ids.kemkes.go.id/kfa', 'code' => $kfaCode, 'display' => $kfaDisplay,
            ]], 'text' => $kfaDisplay],
            'status' => 'active',
            'extension' => [[
                'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/MedicationType',
                'valueCodeableConcept' => ['coding' => [[
                    'system' => 'https://terminology.kemkes.go.id/CodeSystem/medication-type', 'code' => 'NC', 'display' => 'Non-compound',
                ]]],
            ]],
        ];
        if ($drug->dosage_form_code) {
            $medication['form'] = ['coding' => [[
                'system' => $drug->dosage_form_system ?: 'http://terminology.kemkes.go.id/CodeSystem/medication-form',
                'code' => $drug->dosage_form_code, 'display' => $drug->dosage_form,
            ]], 'text' => $drug->dosage_form];
        }
        if ($medicationId) $medication['id'] = $medicationId;

        $identifierBase = 'http://sys-ids.kemkes.go.id/';
        $identifierType = $dispense ? 'medicationdispense' : 'prescription';
        $identifiers = [[
            'use' => 'official', 'system' => $identifierBase . $identifierType . '/' . $clinic->satusehat_organization_id,
            'value' => (string) $prescription->getKey(),
        ], [
            'use' => 'official', 'system' => $identifierBase . 'prescription-item/' . $clinic->satusehat_organization_id,
            'value' => (string) $prescription->getKey(),
        ]];
        $resource = [
            'resourceType' => $resourceType,
            'meta' => ['profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/' . $resourceType]],
            'identifier' => $identifiers,
            'status' => $dispense ? 'completed' : ($cancelled ? 'cancelled' : 'active'),
            'medicationReference' => ['reference' => $medicationReference, 'display' => $kfaDisplay],
            'subject' => ['reference' => 'Patient/' . $patient->satusehat_patient_id, 'display' => $patient->name],
        ];

        if ($dispense) {
            $resource['category'] = ['coding' => [[
                'system' => 'http://terminology.hl7.org/fhir/CodeSystem/medicationdispense-category', 'code' => 'outpatient', 'display' => 'Outpatient',
            ]]];
            $resource['context'] = ['reference' => 'Encounter/' . $appointment->satusehat_encounter_id];
            $resource['authorizingPrescription'] = [['reference' => 'MedicationRequest/' . $prescription->satusehat_medication_request_id]];
            $resource['quantity'] = $this->quantity((int) $prescription->dispensed_quantity, $prescription->quantity_unit ?: 'TAB');
            $resource['dosageInstruction'] = [$this->dosageInstruction($prescription)];
            $resource['whenPrepared'] = optional($prescription->dispensed_at)->toIso8601String() ?: now()->toIso8601String();
            $resource['whenHandedOver'] = optional($prescription->dispensed_at)->toIso8601String() ?: now()->toIso8601String();
            $actor = $prescription->dispenser?->satusehat_practitioner_id
                ? ['reference' => 'Practitioner/' . $prescription->dispenser->satusehat_practitioner_id, 'display' => $prescription->dispenser->name]
                : ['reference' => 'Organization/' . $clinic->satusehat_organization_id, 'display' => $clinic->name];
            $resource['performer'] = [['actor' => $actor]];
            if ($clinic->satusehat_location_id) $resource['location'] = ['reference' => 'Location/' . $clinic->satusehat_location_id, 'display' => $clinic->name];
        } else {
            $resource['intent'] = 'order';
            $resource['category'] = [['coding' => [[
                'system' => 'http://terminology.hl7.org/CodeSystem/medicationrequest-category', 'code' => 'outpatient', 'display' => 'Outpatient',
            ]]]];
            if ($appointment->satusehat_encounter_id) $resource['encounter'] = ['reference' => 'Encounter/' . $appointment->satusehat_encounter_id];
            $resource['authoredOn'] = ($appointment->schedule_date ?? now())->toDateString();
            $resource['requester'] = ['reference' => 'Practitioner/' . $doctor->satusehat_practitioner_id, 'display' => $doctor->name];
            $resource['dosageInstruction'] = [$this->dosageInstruction($prescription)];
            $resource['dispenseRequest'] = [
                'quantity' => $this->quantity((int) $prescription->quantity, $prescription->quantity_unit ?: 'TAB'),
            ];
            if ($prescription->duration_days) $resource['dispenseRequest']['expectedSupplyDuration'] = ['value' => (int) $prescription->duration_days, 'unit' => 'day', 'system' => 'http://unitsofmeasure.org', 'code' => 'd'];
        }
        if ($remoteId) $resource['id'] = $remoteId;

        $response = $client->transaction([
            'resourceType' => 'Bundle', 'type' => 'transaction',
            'entry' => [
                ['fullUrl' => $medicationFullUrl, 'resource' => $medication, 'request' => ['method' => $medicationId ? 'PUT' : 'POST', 'url' => $medicationId ? 'Medication/' . $medicationId : 'Medication']],
                ['fullUrl' => $recordFullUrl, 'resource' => $resource, 'request' => ['method' => $remoteId ? 'PUT' : 'POST', 'url' => $remoteId ? $resourceType . '/' . $remoteId : $resourceType]],
            ],
        ]);

        $medicationId ??= $this->idFromLocation(data_get($response, 'entry.0.response.location'), 'Medication');
        $remoteId ??= $this->idFromLocation(data_get($response, 'entry.1.response.location'), $resourceType);
        if (!$medicationId || !$remoteId) throw new RuntimeException("Respons SATUSEHAT tidak berisi ID Medication/{$resourceType}.");
        $updates = [
            'satusehat_sync_status' => $dispense ? 'dispensed' : ($cancelled ? 'cancelled' : 'synced'),
            'satusehat_sync_error' => null, 'satusehat_synced_at' => now(),
        ];
        $updates[$dispense ? 'satusehat_medication_dispense_id' : 'satusehat_medication_request_id'] = $remoteId;
        $prescription->forceFill($updates)->saveQuietly();
        $drug->forceFill(['satusehat_medication_id' => $medicationId])->saveQuietly();
    }

    private function dosageInstruction(Prescription $prescription): array
    {
        $frequency = (string) $prescription->frequency;
        [$count, $period, $unit] = match ($frequency) {
            'QD' => [1, 1, 'd'], 'BID' => [2, 1, 'd'], 'TID' => [3, 1, 'd'], 'QID' => [4, 1, 'd'],
            'Q4H' => [1, 4, 'h'], 'Q6H' => [1, 6, 'h'],
            default => throw new RuntimeException('Frekuensi resep belum memakai kode yang didukung SATUSEHAT.'),
        };
        $routes = [
            'oral' => ['26643006', 'Oral route'], 'intravenous' => ['47625008', 'Intravenous route'],
            'topical' => ['6064005', 'Topical route'], 'inhalation' => ['447694001', 'Inhalation route'],
            'ophthalmic' => ['54485002', 'Ophthalmic route'], 'rectal' => ['37161004', 'Rectal route'],
            'vaginal' => ['16857009', 'Vaginal route'],
        ];
        $route = $routes[$prescription->route] ?? throw new RuntimeException('Rute pemberian resep belum memiliki pemetaan SNOMED CT.');
        $dosage = [
            'sequence' => 1,
            'text' => $prescription->dosage_instruction,
            'timing' => [
                'repeat' => ['frequency' => $count, 'period' => $period, 'periodUnit' => $unit],
                'code' => ['coding' => [[
                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-GTSAbbreviation', 'code' => $frequency, 'display' => $frequency,
                ]]],
            ],
            'route' => ['coding' => [[
                'system' => 'http://snomed.info/sct', 'code' => $route[0], 'display' => $route[1],
            ]], 'text' => $route[1]],
            'doseAndRate' => [['doseQuantity' => [
                'value' => (float) $prescription->dose_value, 'unit' => $prescription->dose_unit,
                'system' => 'http://unitsofmeasure.org', 'code' => $prescription->dose_unit,
            ]]],
        ];
        return $dosage;
    }

    private function quantity(int $value, string $unit): array
    {
        $system = in_array($unit, ['TAB', 'CAP'], true)
            ? 'http://terminology.hl7.org/CodeSystem/v3-orderableDrugForm'
            : 'http://unitsofmeasure.org';

        return ['value' => $value, 'unit' => $unit, 'system' => $system, 'code' => $unit];
    }

    private function idFromLocation(?string $location, string $resourceType): ?string
    {
        if (!$location) return null;
        $segments = explode('/', trim(parse_url($location, PHP_URL_PATH) ?: $location, '/'));
        $index = array_search($resourceType, $segments, true);
        return $index === false ? null : ($segments[$index + 1] ?? null);
    }
}
