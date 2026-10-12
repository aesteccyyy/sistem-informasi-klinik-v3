<?php

namespace App\Services\Satusehat;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class KfaCatalogClient
{
    public function isConfigured(): bool
    {
        return filled(config('satusehat.kfa.access_token'))
            || (filled(config('satusehat.kfa.client_id')) && filled(config('satusehat.kfa.client_secret')));
    }

    /** @return array<string, string> Map of KFA code to display label. */
    public function searchMedicines(string $keyword): array
    {
        $keyword = trim($keyword);
        if (mb_strlen($keyword) < 2 || !$this->isConfigured()) {
            return [];
        }

        $response = Http::acceptJson()
            ->withToken($this->accessToken())
            ->timeout(config('satusehat.timeout'))
            ->get(rtrim(config('satusehat.kfa.base_url'), '/') . '/products/all', [
                'page' => 1,
                'size' => 50,
                'product_type' => 'farmasi',
                'keyword' => $keyword,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Pencarian KFA gagal (HTTP ' . $response->status() . '). Periksa akses API KFA dan konfigurasi kredensial.');
        }

        $products = data_get($response->json(), 'items.data', []);
        $options = [];

        foreach (is_array($products) ? $products : [] as $product) {
            if (!is_array($product) || ($product['active'] ?? false) !== true || ($product['state'] ?? null) !== 'valid') {
                continue;
            }

            $actualCode = (string) ($product['kfa_code'] ?? '');
            $actualName = (string) ($product['name'] ?? '');
            if (preg_match('/^93\d{6}$/', $actualCode) && $actualName !== '') {
                $options[$actualCode] = $actualName . ' — KFA ' . $actualCode . ' (produk aktual)';
            }

            $templateCode = (string) data_get($product, 'product_template.kfa_code', '');
            $templateName = (string) data_get($product, 'product_template.display_name', data_get($product, 'product_template.name', ''));
            if (preg_match('/^92\d{6}$/', $templateCode) && $templateName !== '') {
                $options[$templateCode] = $templateName . ' — KFA ' . $templateCode . ' (template)';
            }
        }

        return $options;
    }

    public function findMedicineDisplay(string $code): ?string
    {
        if (!$this->isConfigured() || !preg_match('/^9[23]\d{6}$/', $code)) {
            return null;
        }

        $response = Http::acceptJson()
            ->withToken($this->accessToken())
            ->timeout(config('satusehat.timeout'))
            ->get(rtrim(config('satusehat.kfa.base_url'), '/') . '/products', [
                'identifier' => 'kfa',
                'code' => $code,
            ]);

        if (!$response->successful()) {
            return null;
        }

        $product = $response->json('result', []);
        if (($product['kfa_code'] ?? null) === $code) {
            return $product['name'] ?? $product['display_name'] ?? null;
        }

        $template = $product['product_template'] ?? [];
        return ($template['kfa_code'] ?? null) === $code
            ? ($template['display_name'] ?? $template['name'] ?? null)
            : null;
    }

    private function accessToken(): string
    {
        if (filled(config('satusehat.kfa.access_token'))) {
            return (string) config('satusehat.kfa.access_token');
        }

        $cacheKey = 'satusehat.kfa.access_token.' . sha1((string) config('satusehat.kfa.client_id'));

        return Cache::remember($cacheKey, now()->addMinutes(50), function (): string {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(config('satusehat.timeout'))
                ->post(config('satusehat.kfa.token_url'), [
                    'client_id' => config('satusehat.kfa.client_id'),
                    'client_secret' => config('satusehat.kfa.client_secret'),
                ]);

            if (!$response->successful() || !filled($response->json('access_token'))) {
                throw new RuntimeException('Autentikasi API KFA gagal. Pastikan kredensial KFA telah disetujui dan sesuai environment.');
            }

            return (string) $response->json('access_token');
        });
    }
}
