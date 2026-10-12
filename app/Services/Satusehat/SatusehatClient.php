<?php

namespace App\Services\Satusehat;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SatusehatClient
{
    public function isConfigured(): bool
    {
        return filled(config('satusehat.client_id'))
            && filled(config('satusehat.client_secret'));
    }

    public function create(string $resourceType, array $resource): array
    {
        return $this->request('post', $resourceType, $resource);
    }

    public function update(string $resourceType, string $resourceId, array $resource): array
    {
        return $this->request('put', $resourceType . '/' . rawurlencode($resourceId), $resource);
    }

    public function patch(string $resourceType, string $resourceId, array $operations): array
    {
        $fhirUrl = config('satusehat.fhir_url')
            ?: config('satusehat.base_url') . '/fhir-r4/v1';

        $response = Http::acceptJson()
            ->asJson()
            ->withToken($this->accessToken())
            ->timeout(config('satusehat.timeout'))
            ->patch(rtrim($fhirUrl, '/') . '/' . $resourceType . '/' . rawurlencode($resourceId), $operations);

        Log::info('SATUSEHAT FHIR request completed', [
            'method' => 'PATCH',
            'resource' => $resourceType . '/' . $resourceId,
            'status' => $response->status(),
        ]);

        $this->throwIfFailed($response);

        return $response->json() ?? [];
    }

    public function get(string $resourceType, string $resourceId): array
    {
        return $this->request('get', $resourceType . '/' . rawurlencode($resourceId), []);
    }

    public function transaction(array $bundle): array
    {
        $fhirUrl = config('satusehat.fhir_url')
            ?: config('satusehat.base_url') . '/fhir-r4/v1';

        $response = Http::acceptJson()
            ->asJson()
            ->withToken($this->accessToken())
            ->timeout(config('satusehat.timeout'))
            ->post(rtrim($fhirUrl, '/'), $bundle);

        Log::info('SATUSEHAT FHIR request completed', [
            'method' => 'POST',
            'resource' => 'Bundle',
            'status' => $response->status(),
        ]);

        $this->throwIfFailed($response);

        return $response->json() ?? [];
    }

    public function findPatientByNik(string $nik): ?array
    {
        $fhirUrl = config('satusehat.fhir_url')
            ?: config('satusehat.base_url') . '/fhir-r4/v1';

        $response = Http::acceptJson()
            ->withToken($this->accessToken())
            ->timeout(config('satusehat.timeout'))
            ->get(rtrim($fhirUrl, '/') . '/Patient', [
                'identifier' => 'https://fhir.kemkes.go.id/id/nik|' . $nik,
            ]);

        Log::info('SATUSEHAT FHIR request completed', [
            'method' => 'GET',
            'resource' => 'Patient',
            'operation' => 'find-by-nik',
            'status' => $response->status(),
        ]);

        $this->throwIfFailed($response);

        return data_get($response->json(), 'entry.0.resource');
    }

    private function request(string $method, string $path, array $payload): array
    {
        $fhirUrl = config('satusehat.fhir_url')
            ?: config('satusehat.base_url') . '/fhir-r4/v1';

        $response = Http::acceptJson()
            ->asJson()
            ->withToken($this->accessToken())
            ->timeout(config('satusehat.timeout'))
            ->{$method}(rtrim($fhirUrl, '/') . '/' . ltrim($path, '/'), $payload);

        Log::info('SATUSEHAT FHIR request completed', [
            'method' => strtoupper($method),
            'resource' => $path,
            'status' => $response->status(),
        ]);

        $this->throwIfFailed($response);

        return $response->json() ?? [];
    }

    private function accessToken(): string
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('SATUSEHAT_CLIENT_ID and SATUSEHAT_CLIENT_SECRET are required.');
        }

        $cacheKey = 'satusehat.access_token.' . sha1((string) config('satusehat.client_id'));

        return Cache::remember($cacheKey, now()->addMinutes(50), function (): string {
            $tokenUrl = config('satusehat.token_url')
                ?: config('satusehat.base_url') . '/oauth2/v1/accesstoken?grant_type=client_credentials';

            $response = Http::asForm()
    ->timeout(config('satusehat.timeout'))
    ->post($tokenUrl, [
        'client_id' => config('satusehat.client_id'),
        'client_secret' => config('satusehat.client_secret'),
    ]);

            Log::info('SATUSEHAT authentication completed', [
                'status' => $response->status(),
            ]);

            $this->throwIfFailed($response);

            $token = $response->json('access_token');
            if (!filled($token)) {
                throw new RuntimeException('SATUSEHAT authentication response did not contain an access token.');
            }

            return $token;
        });
    }

    private function throwIfFailed(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        Log::warning('SATUSEHAT request failed', [
            'status' => $response->status(),
            'resource_type' => $response->json('resourceType'),
        ]);

        $issues = $response->json('issue', []);
        $details = collect(is_array($issues) ? $issues : [])
            ->filter(fn ($issue): bool => is_array($issue))
            ->map(function (array $issue): string {
                $parts = [];

                if (filled($issue['code'] ?? null)) {
                    $parts[] = 'code=' . $issue['code'];
                }

                if (filled($issue['diagnostics'] ?? null)) {
                    $parts[] = 'diagnostics=' . mb_substr($issue['diagnostics'], 0, 2000);
                }

                if (filled(data_get($issue, 'details.text'))) {
                    $parts[] = 'details=' . mb_substr(data_get($issue, 'details.text'), 0, 1000);
                }

                $expressions = $issue['expression'] ?? [];
                if (is_string($expressions)) {
                    $expressions = [$expressions];
                }
                if (is_array($expressions) && $expressions !== []) {
                    $parts[] = 'expression=' . implode(', ', array_filter($expressions, 'is_string'));
                }

                return implode('; ', $parts);
            })
            ->filter()
            ->implode(' | ');

        if ($details === '') {
            $details = mb_substr((string) ($response->json('error_description') ?? $response->json('error') ?? ''), 0, 2000);
        }

        Log::warning('SATUSEHAT response details', [
            'status' => $response->status(),
            'details' => $details,
        ]);

        $message = 'SATUSEHAT request failed with HTTP ' . $response->status();

        if ($details !== '') {
            $message .= ': ' . $details;
        }

        throw new RuntimeException($message);
    }
}
