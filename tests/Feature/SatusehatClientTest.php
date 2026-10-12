<?php

use App\Services\Satusehat\SatusehatClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

it('authenticates and searches or creates FHIR resources', function () {
    config([
        'cache.default' => 'array',
        'satusehat.base_url' => 'https://fhir.test',
        'satusehat.token_url' => 'https://auth.test/oauth2/v1/accesstoken',
        'satusehat.fhir_url' => 'https://fhir.test/fhir-r4/v1',
        'satusehat.client_id' => 'test-client',
        'satusehat.client_secret' => 'test-secret',
        'satusehat.timeout' => 3,
    ]);
    Cache::flush();

    Http::fake([
        'https://auth.test/*' => Http::response([
            'access_token' => 'test-access-token',
            'expires_in' => 3600,
        ]),
        'https://fhir.test/fhir-r4/v1/Patient*' => Http::sequence()
            ->push([
                'resourceType' => 'Bundle',
                'entry' => [['resource' => ['id' => 'existing-patient-id']]],
            ])
            ->push(['resourceType' => 'Patient', 'id' => 'created-patient-id'], 201),
    ]);

    $client = app(SatusehatClient::class);

    expect($client->findPatientByNik('3201010101010001')['id'])
        ->toBe('existing-patient-id');
    expect($client->create('Patient', ['resourceType' => 'Patient'])['id'])
        ->toBe('created-patient-id');

    Http::assertSent(fn (Request $request): bool =>
        str_starts_with($request->url(), 'https://auth.test/')
    );
    Http::assertSent(fn (Request $request): bool =>
        str_starts_with($request->url(), 'https://fhir.test/fhir-r4/v1/Patient')
            && $request->hasHeader('Authorization', 'Bearer test-access-token')
    );
});

it('includes OperationOutcome issue details when a FHIR request fails', function () {
    config([
        'cache.default' => 'array',
        'satusehat.base_url' => 'https://fhir.test',
        'satusehat.token_url' => 'https://auth.test/oauth2/v1/accesstoken',
        'satusehat.fhir_url' => 'https://fhir.test/fhir-r4/v1',
        'satusehat.client_id' => 'test-client-429',
        'satusehat.client_secret' => 'test-secret',
        'satusehat.timeout' => 3,
    ]);
    Cache::flush();

    Http::fake([
        'https://auth.test/*' => Http::response(['access_token' => 'test-access-token']),
        'https://fhir.test/fhir-r4/v1/Patient' => Http::response([
            'resourceType' => 'OperationOutcome',
            'issue' => [[
                'severity' => 'error',
                'code' => 'throttled',
                'diagnostics' => 'Too many requests',
            ]],
        ], 429),
    ]);

    expect(fn () => app(SatusehatClient::class)->create('Patient', ['resourceType' => 'Patient']))
        ->toThrow(\RuntimeException::class, 'code=throttled; diagnostics=Too many requests');
});

it('includes FHIR issue details and field expressions for validation failures', function () {
    config([
        'cache.default' => 'array',
        'satusehat.base_url' => 'https://fhir.test',
        'satusehat.token_url' => 'https://auth.test/oauth2/v1/accesstoken',
        'satusehat.fhir_url' => 'https://fhir.test/fhir-r4/v1',
        'satusehat.client_id' => 'test-client-400',
        'satusehat.client_secret' => 'test-secret',
        'satusehat.timeout' => 3,
    ]);
    Cache::flush();

    Http::fake([
        'https://auth.test/*' => Http::response(['access_token' => 'test-access-token']),
        'https://fhir.test/fhir-r4/v1/Patient' => Http::response([
            'resourceType' => 'OperationOutcome',
            'issue' => [[
                'severity' => 'error',
                'code' => 'value',
                'details' => ['text' => 'Invalid patient birth date'],
                'expression' => ['Patient.birthDate'],
            ]],
        ], 400),
    ]);

    expect(fn () => app(SatusehatClient::class)->create('Patient', ['resourceType' => 'Patient']))
        ->toThrow(\RuntimeException::class, 'code=value; details=Invalid patient birth date; expression=Patient.birthDate');
});

it('sends FHIR transaction bundles to the FHIR base endpoint', function () {
    config([
        'cache.default' => 'array',
        'satusehat.base_url' => 'https://fhir.test',
        'satusehat.token_url' => 'https://auth.test/oauth2/v1/accesstoken',
        'satusehat.fhir_url' => 'https://fhir.test/fhir-r4/v1',
        'satusehat.client_id' => 'test-client-transaction',
        'satusehat.client_secret' => 'test-secret',
        'satusehat.timeout' => 3,
    ]);
    Cache::flush();

    Http::fake([
        'https://auth.test/*' => Http::response(['access_token' => 'test-access-token']),
        'https://fhir.test/fhir-r4/v1' => Http::response([
            'resourceType' => 'Bundle',
            'type' => 'transaction-response',
            'entry' => [],
        ]),
    ]);

    $bundle = [
        'resourceType' => 'Bundle',
        'type' => 'transaction',
        'entry' => [],
    ];

    expect(app(SatusehatClient::class)->transaction($bundle)['type'])
        ->toBe('transaction-response');

    Http::assertSent(fn (Request $request): bool =>
        $request->url() === 'https://fhir.test/fhir-r4/v1'
            && $request->data() === $bundle
            && $request->hasHeader('Authorization', 'Bearer test-access-token')
    );
});

it('updates an existing FHIR resource by id', function () {
    config([
        'cache.default' => 'array',
        'satusehat.base_url' => 'https://fhir.test',
        'satusehat.token_url' => 'https://auth.test/oauth2/v1/accesstoken',
        'satusehat.fhir_url' => 'https://fhir.test/fhir-r4/v1',
        'satusehat.client_id' => 'test-client-update',
        'satusehat.client_secret' => 'test-secret',
        'satusehat.timeout' => 3,
    ]);
    Cache::flush();

    Http::fake([
        'https://auth.test/*' => Http::response(['access_token' => 'test-access-token']),
        'https://fhir.test/fhir-r4/v1/Patient/P00805884304' => Http::response([
            'resourceType' => 'Patient',
            'id' => 'P00805884304',
        ]),
    ]);

    $resource = [
        'resourceType' => 'Patient',
        'active' => true,
    ];

    expect(app(SatusehatClient::class)->update('Patient', 'P00805884304', $resource)['id'])
        ->toBe('P00805884304');

    Http::assertSent(fn (Request $request): bool =>
        $request->method() === 'PUT'
            && $request->url() === 'https://fhir.test/fhir-r4/v1/Patient/P00805884304'
            && $request->data() === $resource
    );
});

it('patches an existing FHIR resource by id', function () {
    config([
        'cache.default' => 'array',
        'satusehat.base_url' => 'https://fhir.test',
        'satusehat.token_url' => 'https://auth.test/oauth2/v1/accesstoken',
        'satusehat.fhir_url' => 'https://fhir.test/fhir-r4/v1',
        'satusehat.client_id' => 'test-client-patch',
        'satusehat.client_secret' => 'test-secret',
        'satusehat.timeout' => 3,
    ]);
    Cache::flush();

    Http::fake([
        'https://auth.test/*' => Http::response(['access_token' => 'test-access-token']),
        'https://fhir.test/fhir-r4/v1/Patient/P00805884304' => Http::response([
            'resourceType' => 'Patient',
            'id' => 'P00805884304',
        ]),
    ]);

    $operations = [['op' => 'replace', 'path' => '/gender', 'value' => 'male']];

    expect(app(SatusehatClient::class)->patch('Patient', 'P00805884304', $operations)['id'])
        ->toBe('P00805884304');

    Http::assertSent(fn (Request $request): bool =>
        $request->method() === 'PATCH'
            && $request->url() === 'https://fhir.test/fhir-r4/v1/Patient/P00805884304'
            && $request->data() === $operations
    );
});
