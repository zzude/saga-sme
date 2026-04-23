<?php

namespace App\Services\EInvoice;

use App\Models\Invoice;
use App\Models\EInvoiceLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StatusService
{
    public function __construct(
        private AuthService $auth,
    ) {}

    public function poll(Invoice $invoice): array
    {
        // Mock mode
        if (config('einvoice.mock_mode')) {
            return $this->mockPoll($invoice);
        }

        return $this->realPoll($invoice);
    }

    private function realPoll(Invoice $invoice): array
    {
        if (!$invoice->einvoice_submission_uid) {
            throw new \Exception('No submission UID found for invoice ' . $invoice->invoice_number);
        }

        $env     = config('einvoice.env', 'sandbox');
        $baseUrl = config("einvoice.urls.{$env}.api");
        $token   = $this->auth->getToken();

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->get("{$baseUrl}/documentsubmissions/{$invoice->einvoice_submission_uid}");

            $responseData = $response->json();

            // Log
            $this->log($invoice, 'poll', $response->status(), $responseData);

            if (!$response->successful()) {
                throw new \Exception('Poll failed: ' . $response->body());
            }

            $this->updateStatus($invoice, $responseData);

            return $responseData;

        } catch (\Exception $e) {
            Log::error('[EInvoice] Poll error', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function updateStatus(Invoice $invoice, array $data): void
    {
        // Tambah ini ↓
        Log::info('[EInvoice] updateStatus called', [
            'invoice_id' => $invoice->id,
            'invoice_object_id' => spl_object_id($invoice),
        ]);
        
        // LHDN status: Valid | Invalid | Submitted | Cancelled
        $docStatus = strtolower(
            $data['acceptedDocuments'][0]['status']
            ?? $data['status']
            ?? 'processing'
        );

        $statusMap = [
            'valid'     => 'valid',
            'invalid'   => 'rejected',
            'submitted' => 'processing',
            'cancelled' => 'cancelled',
        ];

        $newStatus = $statusMap[$docStatus] ?? 'processing';
        $longId    = $data['acceptedDocuments'][0]['longId'] ?? null;

        \DB::table('invoices')->where('id', $invoice->id)->update([
            'einvoice_status'       => $newStatus,
            'einvoice_long_id'      => $longId ?? $invoice->einvoice_long_id,
            'einvoice_validated_at' => in_array($newStatus, ['valid', 'rejected']) ? now() : null,
            'einvoice_errors'       => $newStatus === 'rejected'
                ? json_encode($data['rejectedDocuments'] ?? null)
                : null,
        ]);

        Log::info('[EInvoice] Status updated', [
            'invoice_id' => $invoice->id,
            'status'     => $newStatus,
            'long_id'    => $longId,
        ]);
    }

    private function mockPoll(Invoice $invoice): array
    {
        // Mock — terus set valid
        $mockResponse = [
            'submissionUid'     => $invoice->einvoice_submission_uid,
            'acceptedDocuments' => [
                [
                    'uuid'   => $invoice->einvoice_uuid,
                    'status' => 'Valid',
                    'longId' => 'MOCK-LONG-' . strtoupper(substr(md5($invoice->id), 0, 10)),
                ]
            ],
            'rejectedDocuments' => [],
        ];

        $this->log($invoice, 'poll_mock', 200, $mockResponse);

        $this->updateStatus($invoice, $mockResponse);

        Log::info('[EInvoice] Mock poll — set valid', [
            'invoice_id' => $invoice->id,
        ]);

        return $mockResponse;
    }

    private function log(
        Invoice $invoice,
        string  $action,
        int     $httpStatus,
        array   $response
    ): void {
        EInvoiceLog::create([
            'invoice_id'       => $invoice->id,
            'action'           => $action,
            'http_status'      => $httpStatus,
            'response_payload' => $response,
            'uuid'             => $invoice->einvoice_uuid,
            'submission_uid'   => $invoice->einvoice_submission_uid,
        ]);
    }
}