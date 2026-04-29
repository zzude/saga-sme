<?php

// app/Console/Commands/SubmitConsolidatedEInvoice.php

namespace App\Console\Commands;

use App\Jobs\SubmitConsolidatedEInvoiceJob;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SubmitConsolidatedEInvoice extends Command
{
    protected $signature = 'einvoice:consolidated
                            {--year= : Year (default: previous month year)}
                            {--month= : Month (default: previous month)}
                            {--company= : Specific company ID (default: all companies)}';

    protected $description = 'Submit consolidated e-Invoice for individual customers to MyInvois (LHDN)';

    public function handle(): void
    {
        $now   = Carbon::now();
        $year  = (int) ($this->option('year')  ?? $now->copy()->subMonth()->year);
        $month = (int) ($this->option('month') ?? $now->copy()->subMonth()->month);
        $companyId = $this->option('company');

        $companies = $companyId
            ? Company::where('id', $companyId)->get()
            : Company::where('is_active', true)->get();

        $this->info("Submitting consolidated e-Invoice for period: {$year}-{$month}");
        $this->info("Companies: {$companies->count()}");

        foreach ($companies as $company) {
            $this->line("  → Dispatching for: {$company->name} (ID: {$company->id})");
            SubmitConsolidatedEInvoiceJob::dispatch($company->id, $year, $month);
        }

        $this->info('All jobs dispatched.');
    }
}