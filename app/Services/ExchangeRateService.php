<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ExchangeRateService
{
    protected string $baseCurrency = 'MYR';
    protected string $apiUrl = 'https://api.frankfurter.app';

    // -------------------------------------------------------------------------
    // PRIMARY: Get rate for a given currency on a given date
    // Priority: DB (locked/override) → DB (API-fetched) → Live API → Latest fallback
    // -------------------------------------------------------------------------

    public function getRate(string $currency, ?Carbon $date = null): array
    {
        if ($currency === $this->baseCurrency) {
            return [
                'rate'   => 1.00000000,
                'source' => 'AUTO',
                'date'   => ($date ?? now())->toDateString(),
            ];
        }

        $date = $date ?? now();
        $dateStr = $date->toDateString();

        // 1. Check DB for this date (locked/manual takes priority)
        $stored = $this->getStoredRate($currency, $dateStr);

        if ($stored) {
            return [
                'rate'   => (float) $stored->rate,
                'source' => $stored->source === 'MANUAL' ? 'MANUAL' : 'AUTO',
                'date'   => $dateStr,
            ];
        }

        // 2. Fetch from API and store
        try {
            $rate = $this->fetchFromApi($currency, $date);

            if ($rate) {
                $this->storeRate($currency, $rate, $dateStr, 'API');

                return [
                    'rate'   => $rate,
                    'source' => 'AUTO',
                    'date'   => $dateStr,
                ];
            }
        } catch (\Exception $e) {
            Log::warning("ExchangeRateService: API fetch failed for {$currency} on {$dateStr}", [
                'error' => $e->getMessage(),
            ]);
        }

        // 3. Fallback: latest available rate from DB
        $fallback = $this->getLatestStoredRate($currency);

        if ($fallback) {
            Log::info("ExchangeRateService: Using fallback rate for {$currency} (last known: {$fallback->rate_date})");

            return [
                'rate'   => (float) $fallback->rate,
                'source' => 'MANUAL', // flag as manual since it's not date-accurate
                'date'   => $fallback->rate_date,
            ];
        }

        // 4. No rate available at all
        throw new \RuntimeException("No exchange rate available for {$currency}. Please set a manual rate in Settings → Exchange Rates.");
    }

    // -------------------------------------------------------------------------
    // Fetch and store rates for all active foreign currencies (called by scheduler)
    // -------------------------------------------------------------------------

    public function fetchAndStoreAll(?Carbon $date = null): array
    {
        $date    = $date ?? now();
        $dateStr = $date->toDateString();
        $results = [];

        $currencies = DB::table('currencies')
            ->where('is_active', true)
            ->where('code', '!=', $this->baseCurrency)
            ->pluck('code');

        foreach ($currencies as $currency) {
            try {
                // Skip if locked rate already exists for this date
                $existing = $this->getStoredRate($currency, $dateStr);

                if ($existing && $existing->is_locked) {
                    $results[$currency] = ['status' => 'skipped', 'reason' => 'locked'];
                    continue;
                }

                $rate = $this->fetchFromApi($currency, $date);

                if ($rate) {
                    $this->storeRate($currency, $rate, $dateStr, 'API');
                    $results[$currency] = ['status' => 'ok', 'rate' => $rate];
                } else {
                    $results[$currency] = ['status' => 'failed', 'reason' => 'empty response'];
                }
            } catch (\Exception $e) {
                $results[$currency] = ['status' => 'failed', 'reason' => $e->getMessage()];
                Log::error("ExchangeRateService: Failed to fetch {$currency}", ['error' => $e->getMessage()]);
            }
        }

        // Clear rate cache after bulk fetch
        Cache::forget('fx_rates_' . $dateStr);

        return $results;
    }

    // -------------------------------------------------------------------------
    // Admin manual override — locks the rate for that date
    // -------------------------------------------------------------------------

    public function adminOverride(string $currency, float $rate, Carbon $date): void
    {
        $dateStr = $date->toDateString();

        DB::table('exchange_rates')->updateOrInsert(
            [
                'rate_date'     => $dateStr,
                'from_currency' => $currency,
                'to_currency'   => $this->baseCurrency,
            ],
            [
                'rate'       => $rate,
                'source'     => 'MANUAL',
                'fetched_at' => now(),
                'is_locked'  => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        Cache::forget('fx_rates_' . $dateStr);

        Log::info("ExchangeRateService: Manual override set for {$currency} on {$dateStr} @ {$rate}");
    }

    // -------------------------------------------------------------------------
    // Internal: fetch single currency rate from Frankfurter API
    // Frankfurter stores rates as: X USD = Y MYR
    // We want: 1 USD = ? MYR  (from=USD, to=MYR)
    // -------------------------------------------------------------------------

    protected function fetchFromApi(string $currency, Carbon $date): ?float
    {
        $dateStr = $date->toDateString();
        $today   = now()->toDateString();

        // Frankfurter uses /latest for today, /YYYY-MM-DD for historical
        $endpoint = ($dateStr === $today)
            ? "{$this->apiUrl}/latest"
            : "{$this->apiUrl}/{$dateStr}";

        $response = Http::timeout(10)->get($endpoint, [
            'from' => $currency,
            'to'   => $this->baseCurrency,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException("Frankfurter API returned HTTP {$response->status()}");
        }

        $data = $response->json();

        return isset($data['rates'][$this->baseCurrency])
            ? (float) $data['rates'][$this->baseCurrency]
            : null;
    }

    // -------------------------------------------------------------------------
    // Internal: DB helpers
    // -------------------------------------------------------------------------

    protected function getStoredRate(string $currency, string $dateStr): ?object
    {
        return DB::table('exchange_rates')
            ->where('rate_date', $dateStr)
            ->where('from_currency', $currency)
            ->where('to_currency', $this->baseCurrency)
            ->orderByDesc('is_locked') // locked/manual first
            ->first();
    }

    protected function getLatestStoredRate(string $currency): ?object
    {
        return DB::table('exchange_rates')
            ->where('from_currency', $currency)
            ->where('to_currency', $this->baseCurrency)
            ->orderByDesc('rate_date')
            ->first();
    }

    protected function storeRate(string $currency, float $rate, string $dateStr, string $source): void
    {
        DB::table('exchange_rates')->updateOrInsert(
            [
                'rate_date'     => $dateStr,
                'from_currency' => $currency,
                'to_currency'   => $this->baseCurrency,
            ],
            [
                'rate'       => $rate,
                'source'     => $source,
                'fetched_at' => now(),
                'is_locked'  => false,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    // -------------------------------------------------------------------------
    // Helper: convert foreign amount to MYR
    // -------------------------------------------------------------------------

    public function toBase(float $foreignAmount, string $currency, ?Carbon $date = null): array
    {
        $rateData  = $this->getRate($currency, $date);
        $baseAmount = round($foreignAmount * $rateData['rate'], 2);

        return [
            'foreign_amount' => $foreignAmount,
            'base_amount'    => $baseAmount,
            'rate'           => $rateData['rate'],
            'source'         => $rateData['source'],
            'rate_date'      => $rateData['date'],
        ];
    }
}