<?php

return [
    'base_url' => rtrim(env('SATUSEHAT_BASE_URL', 'https://api-satusehat-stg.dto.kemkes.go.id'), '/'),
    'token_url' => env('SATUSEHAT_TOKEN_URL'),
    'fhir_url' => env('SATUSEHAT_FHIR_URL'),
    'client_id' => env('SATUSEHAT_CLIENT_ID'),
    'client_secret' => env('SATUSEHAT_CLIENT_SECRET'),
    'timeout' => (int) env('SATUSEHAT_TIMEOUT', 20),
    'kfa' => [
        'base_url' => rtrim(env('SATUSEHAT_KFA_BASE_URL', 'https://api-satusehat-stg.dto.kemkes.go.id/kfa-v2'), '/'),
        'token_url' => env('SATUSEHAT_KFA_TOKEN_URL', 'https://api-satusehat-stg.dto.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials'),
        'client_id' => env('SATUSEHAT_KFA_CLIENT_ID'),
        'client_secret' => env('SATUSEHAT_KFA_CLIENT_SECRET'),
        'access_token' => env('SATUSEHAT_KFA_ACCESS_TOKEN'),
    ],
];
