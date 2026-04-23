<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\EInvoice\MyInvoisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SubmitInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;
    public int $backoff = 30; // seconds between retries

    public function __construct(
        public Invoice $invoice
    ) {}

    public function handle(MyInvoisService $myInvois): void
    {
        Log::info('[EInvoice] SubmitInvoiceJob started', [
            'invoice_id'     => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_no,
        ]);
        
        // Load fresh tanpa scope
        $this->invoice = Invoice::withoutGlobalScopes()->find($this->invoice->id);        

        try {
            $result = $myInvois->submit($this->invoice);

            if (isset($result['skipped'])) {
                Log::info('[EInvoice] Submission skipped', [
                    'invoice_id' => $this->invoice->id,
                    'reason'     => $result['status'],
                ]);
                return;
            }

            // Dispatch status check after delay
            CheckInvoiceStatusJob::dispatch($this->invoice)
                ->delay(now()->addSeconds(config('einvoice.poll_delay_seconds', 10)));

            Log::info('[EInvoice] SubmitInvoiceJob completed', [
                'invoice_id' => $this->invoice->id,
            ]);

        } catch (\Exception $e) {
            Log::error('[EInvoice] SubmitInvoiceJob failed', [
                'invoice_id' => $this->invoice->id,
                'error'      => $e->getMessage(),
                'attempt'    => $this->attempts(),
            ]);

            throw $e; // Allow retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[EInvoice] SubmitInvoiceJob permanently failed', [
            'invoice_id' => $this->invoice->id,
            'error'      => $exception->getMessage(),
        ]);

        $this->invoice->update([
            'einvoice_status' => 'rejected',
            'einvoice_errors' => ['message' => $exception->getMessage()],
        ]);
    }
}