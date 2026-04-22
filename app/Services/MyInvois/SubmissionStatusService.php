<?php

namespace App\Services\MyInvois;

use App\Models\MyInvoisProfile;
use App\Models\MyInvoisSubmission;
use Illuminate\Support\Facades\Http;

class SubmissionStatusService
{
    public function __construct(private MyInvoisAuthService $auth) {}

    public function poll(MyInvoisSubmission $submission, MyInvoisProfile $profile): MyInvoisSubmission
    {
        if (!$submission->submission_uid) {
            return $submission;
        }

        $token = $this->auth->getAccessToken($profile);

        $response = Http::withToken($token)
            ->get($profile->getBaseUrl() . "/api/v1.0/documentsubmissions/{$submission->submission_uid}");

        if (!$response->successful()) {
            return $submission;
        }

        $data     = $response->json();
        $docStatus = $data["documentSummary"][0]["status"] ?? null;
        $status   = match($docStatus) {
            "Valid"     => "validated",
            "Invalid"   => "rejected",
            "Cancelled" => "cancelled",
            default      => "processing",
        };

        $submission->update([
            "status"           => $status,
            "document_uid"     => $data["documentSummary"][0]["uuid"] ?? null,
            "long_id"          => $data["documentSummary"][0]["longId"] ?? null,
            "response_payload" => $data,
            "error_message"    => $status === "rejected" ? json_encode($data["documentSummary"][0]["validationResults"] ?? []) : null,
            "validated_at"     => $status === "validated" ? now() : null,
        ]);

        return $submission;
    }
}
