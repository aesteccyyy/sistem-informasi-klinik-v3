<?php

namespace App\Jobs\Satusehat;

use App\Enums\AppointmentStatusEnum;
use App\Models\Appointment;
use App\Services\Satusehat\SatusehatClient;
use App\Services\Satusehat\SatusehatClinicalDataSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SyncAppointmentToSatusehat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $appointmentId)
    {
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(SatusehatClient $client, SatusehatClinicalDataSync $clinicalDataSync): void
    {
        $appointment = Appointment::with([
            'patient',
            'clinic_doctor.user',
            'clinic_doctor.clinic',
        ])->findOrFail($this->appointmentId);

        $appointment->forceFill([
            'satusehat_sync_status' => 'syncing',
            'satusehat_sync_error' => null,
        ])->saveQuietly();

        try {
            if (!$appointment->patient) {
                throw new RuntimeException('Appointment tidak memiliki data pasien.');
            }

            if (!$appointment->patient->satusehat_patient_id) {
                SyncPatientToSatusehat::dispatchSync($appointment->patient->id);
                $appointment->patient->refresh();
            }

            $clinicUser = $appointment->clinic_doctor;
            $clinic = $clinicUser?->clinic;
            $doctor = $clinicUser?->user;

            $requiredMappings = [
                'ID Organization SATUSEHAT klinik' => $clinic?->satusehat_organization_id,
                'ID Location SATUSEHAT klinik' => $clinic?->satusehat_location_id,
                'ID Practitioner SATUSEHAT dokter' => $doctor?->satusehat_practitioner_id,
                'kode diagnosis ICD-10' => $appointment->satusehat_diagnosis_code,
                'ID Patient SATUSEHAT pasien' => $appointment->patient->satusehat_patient_id,
            ];

            foreach ($requiredMappings as $label => $value) {
                if (!filled($value)) {
                    throw new RuntimeException("Lengkapi {$label} sebelum sinkronisasi appointment.");
                }
            }

            $encounterStatus = $this->fhirStatus($appointment->status);
            $encounterStart = $appointment->schedule_date->copy()->startOfDay();
            $statusPeriod = [
                'start' => $encounterStart->toIso8601String(),
            ];

            if ($encounterStatus === 'finished' && $appointment->updated_at->greaterThan($encounterStart)) {
                $statusPeriod['end'] = $appointment->updated_at->toIso8601String();
            }

            $encounterId = $appointment->satusehat_encounter_id;
            $conditionId = $appointment->satusehat_condition_id;
            $fhirUrl = rtrim(config('satusehat.fhir_url')
                ?: config('satusehat.base_url') . '/fhir-r4/v1', '/');
            $encounterFullUrl = $encounterId
                ? $fhirUrl . '/Encounter/' . rawurlencode($encounterId)
                : 'urn:uuid:' . Str::uuid();
            $conditionFullUrl = $conditionId
                ? $fhirUrl . '/Condition/' . rawurlencode($conditionId)
                : 'urn:uuid:' . Str::uuid();
            $encounterReference = $encounterId
                ? 'Encounter/' . $encounterId
                : $encounterFullUrl;
            $conditionReference = $conditionId
                ? 'Condition/' . $conditionId
                : $conditionFullUrl;

            $resource = [
                'resourceType' => 'Encounter',
                'meta' => [
                    'profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/Encounter'],
                ],
                'identifier' => [[
                    'use' => 'official',
                    'system' => 'http://sys-ids.kemkes.go.id/encounter/' . $clinic->satusehat_organization_id,
                    'value' => (string) $appointment->getKey(),
                ]],
                'status' => $encounterStatus,
                'statusHistory' => [[
                    'status' => $encounterStatus,
                    'period' => $statusPeriod,
                ]],
                'class' => [
                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                    'code' => 'AMB',
                    'display' => 'ambulatory',
                ],
                'subject' => [
                    'reference' => 'Patient/' . $appointment->patient->satusehat_patient_id,
                    'display' => $appointment->patient->name,
                ],
                'participant' => [[
                    'individual' => [
                        'reference' => 'Practitioner/' . $doctor->satusehat_practitioner_id,
                        'display' => $doctor->name,
                    ],
                ]],
                'period' => [
                    'start' => $encounterStart->toIso8601String(),
                ],
                'serviceProvider' => [
                    'reference' => 'Organization/' . $clinic->satusehat_organization_id,
                    'display' => $clinic->name,
                ],
                'location' => [[
                    'location' => [
                        'reference' => 'Location/' . $clinic->satusehat_location_id,
                        'display' => $clinic->name,
                    ],
                ]],
                'diagnosis' => [[
                    'condition' => [
                        'reference' => $conditionReference,
                    ],
                    'rank' => 1,
                ]],
            ];

            if ($encounterId) {
                $resource['id'] = $encounterId;
            }

            $condition = [
                'resourceType' => 'Condition',
                'meta' => [
                    'profile' => ['https://fhir.kemkes.go.id/r4/StructureDefinition/Condition'],
                ],
                'identifier' => [[
                    'use' => 'official',
                    'system' => 'http://sys-ids.kemkes.go.id/condition/' . $clinic->satusehat_organization_id,
                    'value' => $appointment->getKey() . '-diagnosis',
                ]],
                'code' => [
                    'coding' => [[
                        'system' => 'http://hl7.org/fhir/sid/icd-10',
                        'code' => $appointment->satusehat_diagnosis_code,
                    ]],
                    'text' => $appointment->diagnostic ?: $appointment->symptoms,
                ],
                'subject' => [
                    'reference' => 'Patient/' . $appointment->patient->satusehat_patient_id,
                ],
                'encounter' => [
                    'reference' => $encounterReference,
                ],
            ];

            if ($conditionId) {
                $condition['id'] = $conditionId;
            }

            $encounterEntry = [
                'fullUrl' => $encounterFullUrl,
                'resource' => $resource,
                'request' => [
                    'method' => $encounterId ? 'PUT' : 'POST',
                    'url' => $encounterId ? 'Encounter/' . $encounterId : 'Encounter',
                ],
            ];
            $conditionEntry = [
                'fullUrl' => $conditionFullUrl,
                'resource' => $condition,
                'request' => [
                    'method' => $conditionId ? 'PUT' : 'POST',
                    'url' => $conditionId ? 'Condition/' . $conditionId : 'Condition',
                ],
            ];
            $response = $client->transaction([
                'resourceType' => 'Bundle',
                'type' => 'transaction',
                'entry' => [$encounterEntry, $conditionEntry],
            ]);

            $encounterId ??= $this->resourceIdFromLocation(
                data_get($response, 'entry.0.response.location'),
                'Encounter',
            );
            $conditionId ??= $this->resourceIdFromLocation(
                data_get($response, 'entry.1.response.location'),
                'Condition',
            );

            if (!filled($encounterId) || !filled($conditionId)) {
                throw new RuntimeException('SATUSEHAT transaction response did not contain Encounter and Condition IDs.');
            }

            $appointment->forceFill([
                'satusehat_encounter_id' => $encounterId,
                'satusehat_condition_id' => $conditionId,
                'satusehat_synced_at' => now(),
            ])->saveQuietly();

            $clinicalDataSync->syncAppointmentData($appointment->fresh(), $client);

            $appointment->forceFill([
                'satusehat_sync_status' => 'synced',
                'satusehat_sync_error' => null,
            ])->saveQuietly();
        } catch (Throwable $exception) {
            $appointment->forceFill([
                'satusehat_sync_status' => 'failed',
                'satusehat_sync_error' => mb_substr($exception->getMessage(), 0, 60000),
            ])->saveQuietly();

            throw $exception;
        }
    }

    private function fhirStatus(AppointmentStatusEnum $status): string
    {
        return match ($status) {
            AppointmentStatusEnum::SCHEDULED => 'planned',
            AppointmentStatusEnum::DIAGNOSED, AppointmentStatusEnum::PREPARED => 'in-progress',
            AppointmentStatusEnum::CONFIRMED => 'finished',
        };
    }

    private function resourceIdFromLocation(?string $location, string $resourceType): ?string
    {
        if (!filled($location)) {
            return null;
        }

        $path = parse_url($location, PHP_URL_PATH) ?: $location;
        $segments = explode('/', trim($path, '/'));
        $index = array_search($resourceType, $segments, true);

        return $index === false ? null : ($segments[$index + 1] ?? null);
    }
}