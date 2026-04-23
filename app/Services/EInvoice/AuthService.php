<?php

namespace App\Services\EInvoice;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthService
{
    private string $identityUrl;
    private string $clientId;
    private string $clientSecret;
    private string $cacheKey;
    private int $cacheTtl;

    public function __construct()
    {
        $env = config('einvoice.env', 'sandbox');

        $this->identityUrl   = config("einvoice.urls.{$env}.identity");
        $this->clientId      = config('einvoice.client_id');
        $this->clientSecret  = config('einvoice.client_secret');
        $this->cacheKey      = config('einvoice.token_cache_key');
        $this->cacheTtl      = config('einvoice.token_cache_ttl', 50);
    }

    public function getToken(): string
    {
        // Mock mode — return dummy token
        if (config('einvoice.mock_mode')) {
            return 'mock-token-' . now()->timestamp;
        }

        // Return cached token if still valid
        if (Cache::has($this->cacheKey)) {
            return Cache::get($this->cacheKey);
        }

        return $this->fetchNewToken();
    }

    private function fetchNewToken(): string
    {
        try {
            $response = Http::asForm()->post($this->identityUrl, [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type'    => 'client_credentials',
                'scope'         => 'InvoicingAPI',
            ]);

            if (!$response->successful()) {
                throw new \Exception('MyInvois auth failed: ' . $response->body());
            }

            $token = $response->json('access_token');

            // Cache token for 50 minutes (expires at 60min)
            Cache::put($this->cacheKey, $token, now()->addMinutes($this->cacheTtl));

            Log::info('[EInvoice] Token refreshed successfully');

            return $token;

        } catch (\Exception $e) {
            Log::error('[EInvoice] Auth failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function clearToken(): void
    {
        Cache::forget($this->cacheKey);
    }
}