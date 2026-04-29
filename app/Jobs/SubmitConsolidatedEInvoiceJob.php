<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\EInvoice\MyInvoisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SubmitConsolidatedEInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $companyId,
        public readonly int $year,
        public readonly int $month,
    ) {}

    public function handle(MyInvoisService $myInvoisService): void
    {
        $period = Carbon::createFromDate($this->year, $this->month, 1);

        $invoices = Invoice::query()
            ->where('company_id', $this->companyId)
            ->whereIn('status', ['posted', 'sent'])
            ->whereNotIn('einvoice_status', ['valid', 'submitted', 'processing'])
            ->whereHas('customer', fn ($q) => $q->where('is_individual', true))
            ->whereYear('date', $this->year)
            ->whereMonth('date', $this->month)
            ->with('customer')
            ->get();

        if ($invoices->isEmpty()) {
            Log::info("[ConsolidatedEInvoice] No invoices to submit — company {$this->companyId} period {$period->format('Y-m')}");
            return;
        }

        Log::info("[ConsolidatedEInvoice] Submitting {$invoices->count()} invoices — company {$this->companyId} period {$period->format('Y-m')}");

        $submitted = 0;
        $failed    = 0;

        foreach ($invoices as $invoice) {
            try {
                $myInvoisService->submit($invoice);
                $submitted++;
            } catch (\Exception $e) {
                $failed++;
                Log::error("[ConsolidatedEInvoice] Failed invoice {$invoice->invoice_no}", [
                    'invoice_id' => $invoice->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        Log::info("[ConsolidatedEInvoice] Done — submitted: {$submitted}, failed: {$failed}, company: {$this->companyId}, period: {$period->format('Y-m')}");
    }
}