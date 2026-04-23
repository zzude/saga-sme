<?php

namespace App\Services\EInvoice;

use App\Models\Invoice;

class MyInvoisService
{
    public function __construct(
        private AuthService       $auth,
        private PayloadBuilder    $builder,
        private SubmissionService $submission,
        private StatusService     $status,
    ) {}

    public function submit(Invoice $invoice): array
    {
        return $this->submission->submit($invoice);
    }

    public function pollStatus(Invoice $invoice): array
    {
        return $this->status->poll($invoice);
    }

    public function clearToken(): void
    {
        $this->auth->clearToken();
    }

    public function previewPayload(Invoice $invoice): array
    {
        return $this->builder->build($invoice);
    }
}