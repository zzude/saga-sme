<?php

namespace App\Jobs;

use App\Models\MyInvoisProfile;
use App\Models\MyInvoisSubmission;
use App\Services\MyInvois\SubmissionStatusService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PollSubmissionStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 15;

    public function __construct(
        public MyInvoisSubmission $submission,
        public MyInvoisProfile $profile,
    ) {}

    public function handle(SubmissionStatusService $service): void
    {
        $submission = $service->poll($this->submission, $this->profile);

        if ($submission->isPending()) {
            self::dispatch($this->submission, $this->profile)
                ->delay(now()->addSeconds(15));
        }
    }
}
