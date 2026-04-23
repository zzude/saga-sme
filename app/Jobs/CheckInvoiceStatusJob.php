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

class CheckInvoiceStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 5;
    public int $timeout = 60;
    public int $backoff = 15;

    public function __construct(
        public Invoice $invoice
    ) {}

    public function handle(MyInvoisService $myInvois): void
    {
        Log::info('[EInvoice] CheckInvoiceStatusJob started', [
            'invoice_id' => $this->invoice->id,
        ]);

        // Refresh dari DB — ambil status terkini
        $this->invoice = Invoice::withoutGlobalScopes()->find($this->invoice->id);

        // Skip kalau dah final status
        if (in_array($this->invoice->einvoice_status, ['valid', 'rejected', 'cancelled'])) {
            Log::info('[EInvoice] Status check skipped — already final', [
                'invoice_id' => $this->invoice->id,
                'status'     => $this->invoice->einvoice_status,
            ]);
            return;
        }

        try {
            $result = $myInvois->pollStatus($this->invoice);

            // Kalau masih processing, poll semula selepas 30s
            $this->invoice = Invoice::withoutGlobalScopes()->find($this->invoice->id);
            if ($this->invoice->einvoice_status === 'processing') {
                Log::info('[EInvoice] Still processing — requeue', [
                    'invoice_id' => $this->invoice->id,
                ]);

                self::dispatch($this->invoice)
                    ->delay(now()->addSeconds(30));
            }

        } catch (\Exception $e) {
            Log::error('[EInvoice] CheckInvoiceStatusJob failed', [
                'invoice_id' => $this->invoice->id,
                'error'      => $e->getMessage(),
                'attempt'    => $this->attempts(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[EInvoice] CheckInvoiceStatusJob permanently failed', [
            'invoice_id' => $this->invoice->id,
            'error'      => $exception->getMessage(),
        ]);
    }
}