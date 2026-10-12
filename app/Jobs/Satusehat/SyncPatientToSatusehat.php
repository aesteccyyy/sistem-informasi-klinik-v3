<?php

namespace App\Jobs\Satusehat;

use App\Models\Patient;
use App\Jobs\Satusehat\SyncAppointmentToSatusehat;
use App\Services\Satusehat\SatusehatClient;
use App\Services\Satusehat\SatusehatClinicalDataSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class SyncPatientToSatusehat implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $patientId)
    {
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(SatusehatClient $client, SatusehatClinicalDataSync $clinicalDataSync): void
    {
        $patient = Patient::findOrFail($this->patientId);
        $patient->forceFill([
            'satusehat_sync_status' => 'syncing',
            'satusehat_sync_error' => null,
        ])->saveQuietly();

        try {
            if (!$patient->nik || !preg_match('/^\d{16}$/', $patient->nik)) {
                throw new RuntimeException('Isi NIK 16 digit pasien sebelum sinkronisasi.');
            }

            $satusehatPatientId = $patient->satusehat_patient_id;

            if (!$satusehatPatientId) {
                $existing = $client->findPatientByNik($patient->nik);
                $satusehatPatientId = $existing['id'] ?? null;
            }

            $resource = $this->patientResource($patient);

            if ($satusehatPatientId) {
                $remotePatient = $client->get('Patient', $satusehatPatientId);
                $operations = $this->patientPatchOperations($resource, $remotePatient);

                if ($operations !== []) {
                    $client->patch('Patient', $satusehatPatientId, $operations);
                }
            } else {
                $response = $client->create('Patient', $resource);

                $satusehatPatientId = $response['id'] ?? null;
                if (!filled($satusehatPatientId)) {
                    throw new RuntimeException('SATUSEHAT tidak mengembalikan ID Patient.');
                }
            }

            $patient->forceFill(['satusehat_patient_id' => $satusehatPatientId])->saveQuietly();
            $clinicalDataSync->syncPatientData($patient, $client);

            // The patient menu is the single entry point for the complete patient record.
            // The appointment job will not recurse because the patient now has an IHS ID.
            foreach ($patient->appointments()->pluck('id') as $appointmentId) {
                SyncAppointmentToSatusehat::dispatchSync((int) $appointmentId);
            }

            $patient->forceFill([
                'satusehat_sync_status' => 'synced',
                'satusehat_sync_error' => null,
                'satusehat_synced_at' => now(),
            ])->saveQuietly();
        } catch (Throwable $exception) {
            $patient->forceFill([
                'satusehat_sync_status' => 'failed',
                'satusehat_sync_error' => mb_substr($exception->getMessage(), 0, 60000),
            ])->saveQuietly();

            throw $exception;
        }
    }

    private function patientResource(Patient $patient): array
    {
        if ($patient->multiple_birth === null) {
            throw new RuntimeException('Pilih apakah pasien merupakan bagian dari kelahiran kembar sebelum sinkronisasi.');
        }

        $administrativeCodes = [
            'province' => $patient->province_id,
            'city' => $patient->regency_id,
            'district' => $patient->district_id,
            'village' => $patient->village_id,
        ];

        if ($patient->address && collect($administrativeCodes)->contains(fn ($code): bool => !filled($code))) {
            throw new RuntimeException('Lengkapi provinsi, kota/kabupaten, kecamatan, dan kelurahan untuk mengirim alamat SATUSEHAT.');
        }

        $resource = [
            'resourceType' => 'Patient',
            'active' => true,
            'identifier' => [[
                'use' => 'official',
                'system' => 'https://fhir.kemkes.go.id/id/nik',
                'value' => $patient->nik,
            ]],
            'name' => [[
                'use' => 'official',
                'text' => $patient->name,
            ]],
            'gender' => $patient->gender?->value ?: 'unknown',
            'multipleBirthBoolean' => $patient->multiple_birth,
        ];

        if ($patient->birth_date) {
            $resource['birthDate'] = $patient->birth_date->toDateString();
        }

        if ($patient->phone) {
            $resource['telecom'] = [[
                'system' => 'phone',
                'value' => $patient->phone,
                'use' => 'mobile',
            ]];
        }

        if ($patient->address) {
            $resource['address'] = [[
                'use' => 'home',
                'line' => [$patient->address],
                'extension' => [[
                    'url' => 'https://fhir.kemkes.go.id/r4/StructureDefinition/administrativeCode',
                    'extension' => collect($administrativeCodes)
                        ->map(fn ($code, $field): array => [
                            'url' => $field,
                            'valueCode' => (string) $code,
                        ])
                        ->values()
                        ->all(),
                ]],
            ]];
        }

        return $resource;
    }

    private function patientPatchOperations(array $resource, array $remote): array
    {
        $operations = [];

        foreach ([
            'name.0.text' => '/name/0/text',
            'gender' => '/gender',
            'birthDate' => '/birthDate',
        ] as $source => $path) {
            if (!filled(data_get($resource, $source))) {
                continue;
            }

            if (!filled(data_get($remote, $source))) {
                continue;
            }

            $operations[] = [
                'op' => 'replace',
                'path' => $path,
                'value' => data_get($resource, $source),
            ];
        }

        if (filled(data_get($resource, 'address.0'))) {
            if (filled(data_get($remote, 'address.0'))) {
                $operations[] = [
                    'op' => 'replace',
                    'path' => '/address/0',
                    'value' => data_get($resource, 'address.0'),
                ];
            }
        }

        return $operations;
    }
}
