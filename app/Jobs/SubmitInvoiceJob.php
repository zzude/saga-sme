<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\MyInvoisProfile;
use App\Services\MyInvois\MyInvoisSubmissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SubmitInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public Invoice $invoice,
        public MyInvoisProfile $profile,
    ) {}

    public function handle(MyInvoisSubmissionService $service): void
    {
        $submission = $service->submit($this->invoice, $this->profile);

        if ($submission->submission_uid) {
            PollSubmissionStatusJob::dispatch($submission, $this->profile)
                ->delay(now()->addSeconds(10));
        }
    }
}
