<?php

namespace App\Services\EInvoice;

use App\Models\Invoice;
use App\Models\EInvoiceLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubmissionService
{
    public function __construct(
        private AuthService    $auth,
        private PayloadBuilder $builder,
    ) {}

    public function submit(Invoice $invoice): array
    {
        // Tambah ini
        Log::info('[EInvoice] submit() called', [
            'invoice_id'     => $invoice->id,
            'einvoice_status'=> $invoice->einvoice_status,
        ]);
                
        // Idempotency check
        if (in_array($invoice->einvoice_status, ['valid', 'submitted', 'processing'])) {
            Log::info('[EInvoice] Skip submit — already ' . $invoice->einvoice_status, [
                'invoice_id' => $invoice->id,
            ]);
            return ['skipped' => true, 'status' => $invoice->einvoice_status];
        }

        if (config('einvoice.mock_mode')) {
            return $this->mockSubmit($invoice);
        }

        return $this->realSubmit($invoice);
    }

    private function realSubmit(Invoice $invoice): array
    {
        $payload     = $this->builder->build($invoice);
        $jsonPayload = json_encode($payload);
        $hash        = hash('sha256', $jsonPayload);
        $encoded     = base64_encode($jsonPayload);

        $body = [
            'documents' => [
                [
                    'format'       => 'JSON',
                    'document'     => $encoded,
                    'documentHash' => $hash,
                    'codeNumber'   => $invoice->invoice_no,
                ]
            ]
        ];

        $env     = config('einvoice.env', 'sandbox');
        $baseUrl = config("einvoice.urls.{$env}.api");
        $token   = $this->auth->getToken();

        try {
            $response     = Http::withToken($token)
                ->timeout(30)
                ->post("{$baseUrl}/documentsubmissions", $body);

            $responseData = $response->json();

            $this->log($invoice, 'submit', $response->status(), $body, $responseData);

            if (!$response->successful()) {
                $invoice->einvoice_status = 'rejected';
                $invoice->einvoice_errors = $responseData;
                $invoice->save();

                throw new \Exception('Submission failed: ' . $response->body());
            }

            $submissionUid = $responseData['submissionUid'] ?? null;
            $uuid          = $responseData['acceptedDocuments'][0]['uuid'] ?? null;

            $invoice->einvoice_status         = 'submitted';
            $invoice->einvoice_submission_uid = $submissionUid;
            $invoice->einvoice_uuid           = $uuid;
            $invoice->einvoice_submitted_at   = now();
            $invoice->einvoice_errors         = null;
            $invoice->save();

            Log::info('[EInvoice] Submitted successfully', [
                'invoice_id'     => $invoice->id,
                'submission_uid' => $submissionUid,
                'uuid'           => $uuid,
            ]);

            return $responseData;

        } catch (\Exception $e) {
            Log::error('[EInvoice] Submit error', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);

            $invoice->einvoice_status = 'rejected';
            $invoice->save();

            throw $e;
        }
    }

    private function mockSubmit(Invoice $invoice): array
    {
        $submissionUid = 'MOCK-SUB-' . Str::upper(Str::random(10));
        $uuid          = 'MOCK-UUID-' . Str::upper(Str::random(10));

        $mockResponse = [
            'submissionUid'     => $submissionUid,
            'acceptedDocuments' => [
                [
                    'uuid'              => $uuid,
                    'invoiceCodeNumber' => $invoice->invoice_no,
                ]
            ],
            'rejectedDocuments' => [],
        ];

        $this->log($invoice, 'submit_mock', 202, [], $mockResponse);

        \DB::table('invoices')->where('id', $invoice->id)->update([
            'einvoice_status'         => 'submitted',
            'einvoice_submission_uid' => $submissionUid,
            'einvoice_uuid'           => $uuid,
            'einvoice_submitted_at'   => now(),
            'einvoice_errors'         => null,
        ]);

        // Tambah ini
        $check = \DB::table('invoices')->where('id', $invoice->id)->value('einvoice_status');
        Log::info('[EInvoice] After DB update check', [
            'invoice_id' => $invoice->id,
            'db_status'  => $check,
        ]);        

        Log::info('[EInvoice] Mock submitted', [
            'invoice_id' => $invoice->id,
            'uuid'       => $uuid,
        ]);

        return $mockResponse;
    }

    private function log(
        Invoice $invoice,
        string  $action,
        int     $httpStatus,
        array   $request,
        array   $response
    ): void {
        EInvoiceLog::create([
            'invoice_id'       => $invoice->id,
            'action'           => $action,
            'http_status'      => $httpStatus,
            'request_payload'  => $request,
            'response_payload' => $response,
            'submission_uid'   => $response['submissionUid'] ?? null,
            'uuid'             => $response['acceptedDocuments'][0]['uuid'] ?? null,
        ]);
    }
}