<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class FetchExchangeRates extends Command
{
    protected $signature = 'exchange-rates:sync
                            {--date= : Specific date to fetch (YYYY-MM-DD), defaults to today}
                            {--force : Force re-fetch even if rate already exists}';

    protected $description = 'Fetch and store exchange rates from Frankfurter API for all active currencies';

    public function handle(ExchangeRateService $service): int
    {
        $dateStr = $this->option('date') ?? now()->toDateString();

        try {
            $date = Carbon::parse($dateStr);
        } catch (\Exception $e) {
            $this->error("Invalid date format: {$dateStr}. Use YYYY-MM-DD.");
            return self::FAILURE;
        }

        $this->info("Fetching exchange rates for {$dateStr}...");

        $results = $service->fetchAndStoreAll($date);

        if (empty($results)) {
            $this->warn('No active foreign currencies found. Check currencies table.');
            return self::SUCCESS;
        }

        // Display results table
        $rows = [];
        $hasFailure = false;

        foreach ($results as $currency => $result) {
            $status = match ($result['status']) {
                'ok'      => '<fg=green>✅ OK</>',
                'skipped' => '<fg=yellow>⏭ Skipped</>',
                'failed'  => '<fg=red>❌ Failed</>',
                default   => $result['status'],
            };

            $detail = match ($result['status']) {
                'ok'      => number_format($result['rate'], 6),
                'skipped' => $result['reason'],
                'failed'  => $result['reason'],
                default   => '',
            };

            $rows[] = [$currency, 'MYR', $status, $detail];

            if ($result['status'] === 'failed') {
                $hasFailure = true;
            }
        }

        $this->table(['From', 'To', 'Status', 'Rate / Reason'], $rows);

        if ($hasFailure) {
            $this->warn('Some rates failed to fetch. Check logs or set manual rates in Settings → Exchange Rates.');
            return self::FAILURE;
        }

        $this->info('Exchange rates synced successfully.');
        return self::SUCCESS;
    }
}