<?php

namespace App\Services\MyInvois;

use App\Models\Invoice;
use App\Models\MyInvoisProfile;
use App\Models\MyInvoisSubmission;
use Illuminate\Support\Facades\Http;

class MyInvoisSubmissionService
{
    public function __construct(
        private MyInvoisAuthService $auth,
        private UBLInvoiceTransformer $transformer,
    ) {}

    public function submit(Invoice $invoice, MyInvoisProfile $profile): MyInvoisSubmission
    {
        $submission = MyInvoisSubmission::firstOrCreate(
            ["invoice_id" => $invoice->id, "company_id" => $invoice->company_id],
            ["invoice_no" => $invoice->invoice_no, "status" => "draft"]
        );

        $ublPayload = $this->transformer->transform($invoice, $profile);
        $json       = json_encode($ublPayload);
        $base64     = base64_encode($json);
        $hash       = hash("sha256", $json);

        $token = $this->auth->getAccessToken($profile);

        $response = Http::withToken($token)
            ->post($profile->getBaseUrl() . "/api/v1.0/documentsubmissions/", [
                "documents" => [[
                    "format"       => "JSON",
                    "document"     => $base64,
                    "documentHash" => $hash,
                    "codeNumber"   => $invoice->invoice_no,
                ]],
            ]);

        $submission->update([
            "ubl_payload"      => $ublPayload,
            "response_payload" => $response->json(),
            "status"           => $response->successful() ? "submitted" : "rejected",
            "submission_uid"   => $response->json("submissionUid") ?? null,
            "error_message"    => $response->successful() ? null : $response->body(),
            "submitted_at"     => $response->successful() ? now() : null,
            "retry_count"      => $submission->retry_count + 1,
        ]);

        return $submission;
    }
}
