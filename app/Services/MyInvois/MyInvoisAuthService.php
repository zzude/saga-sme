<?php

namespace App\Services\MyInvois;

use App\Models\MyInvoisProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MyInvoisAuthService
{
    public function getAccessToken(MyInvoisProfile $profile): ?string
    {
        $cacheKey = "myinvois_token_{$profile->company_id}";

        if ($profile->isTokenValid()) {
            return $profile->access_token;
        }

        return Cache::remember($cacheKey, 3500, function () use ($profile) {
            return $this->requestNewToken($profile);
        });
    }

    private function requestNewToken(MyInvoisProfile $profile): ?string
    {
        $response = Http::asForm()->post($profile->getBaseUrl() . "/connect/token", [
            "client_id"     => $profile->client_id,
            "client_secret" => $profile->client_secret,
            "grant_type"    => "client_credentials",
            "scope"         => "InvoicingAPI",
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();
        $expiresAt = now()->addSeconds($data["expires_in"] ?? 3600);

        $profile->update([
            "access_token"    => $data["access_token"],
            "token_expires_at" => $expiresAt,
        ]);

        return $data["access_token"];
    }
}
