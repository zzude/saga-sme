<?php

namespace App\Services;

use App\Models\BillplzBill;
use App\Models\Invoice;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BillplzService
{
    private Client $client;
    private string $baseUrl;
    private string $apiKey;
    private string $collectionId;
    private string $xSignature;

    public function __construct()
    {
        $env                = config('billplz.env', 'sandbox');
        $this->baseUrl      = config("billplz.urls.{$env}");
        $this->apiKey       = config('billplz.api_key');
        $this->collectionId = config('billplz.collection_id');
        $this->xSignature   = config('billplz.x_signature');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'auth'     => [$this->apiKey, ''],
            'timeout'  => 30,
            'headers'  => ['Accept' => 'application/json'],
        ]);
    }

    // ── Create Bill ───────────────────────────────────────────────
    public function createBill(
        string $name,
        string $email,
        string $phone,
        string $description,
        float  $amount,
        string $referenceNo,
        string $callbackUrl,
        string $redirectUrl,
    ): array {
        $response = $this->client->post('/bills', [
            'form_params' => [
                'collection_id'       => $this->collectionId,
                'email'               => $email,
                'mobile'              => $phone,
                'name'                => $name,
                'amount'              => (int) round($amount * 100), // cents
                'callback_url'        => $callbackUrl,
                'description'         => $description,
                'reference_1_label'   => 'Reference No',
                'reference_1'         => $referenceNo,
                'redirect_url'        => $redirectUrl,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    // ── Get Bill ──────────────────────────────────────────────────
    public function getBill(string $billId): array
    {
        $response = $this->client->get("/bills/{$billId}");
        return json_decode($response->getBody()->getContents(), true);
    }

    // ── Verify X-Signature ────────────────────────────────────────
    public function verifySignature(array $data): bool
    {
        // Billplz X-Signature verification
        $xSignatureKey = $this->xSignature;

        // Sort keys alphabetically, exclude x_signature
        $filtered = collect($data)
            ->except('x_signature')
            ->sortKeys()
            ->map(fn ($v) => (string) $v)
            ->toArray();

        $source = collect($filtered)
            ->map(fn ($v, $k) => "{$k}|{$v}")
            ->implode('|');

        $computed = hash_hmac('sha256', $source, $xSignatureKey);

        return hash_equals($computed, $data['x_signature'] ?? '');
    }

    // ── Create Invoice Payment Bill ───────────────────────────────
    public function createInvoiceBill(Invoice $invoice): BillplzBill
    {
        $customer = $invoice->customer;
        $company  = $invoice->company;

        $callbackUrl = route('billplz.callback');
        $redirectUrl = route('billplz.redirect', ['invoice' => $invoice->id]);

        $data = $this->createBill(
            name:         $customer->name,
            email:        $customer->email ?? $company->email ?? 'noreply@sagasme.com',
            phone:        $customer->phone ?? '',
            description:  'Invoice ' . $invoice->invoice_no . ' — ' . $company->name,
            amount:       (float) $invoice->balance_due,
            referenceNo:  $invoice->invoice_no,
            callbackUrl:  $callbackUrl,
            redirectUrl:  $redirectUrl,
        );

        $env        = config('billplz.env', 'sandbox');
        $paymentUrl = config("billplz.payment_url.{$env}") . '/' . $data['id'];

        return BillplzBill::create([
            'company_id'     => $invoice->company_id,
            'billplz_id'     => $data['id'],
            'collection_id'  => $this->collectionId,
            'billable_type'  => Invoice::class,
            'billable_id'    => $invoice->id,
            'reference_no'   => $invoice->invoice_no,
            'description'    => 'Invoice ' . $invoice->invoice_no,
            'amount'         => $invoice->balance_due,
            'payer_name'     => $customer->name,
            'payer_email'    => $customer->email,
            'payer_phone'    => $customer->phone,
            'status'         => 'pending',
            'url'            => $paymentUrl,
        ]);
    }

    // ── Handle Callback ───────────────────────────────────────────
    public function handleCallback(array $data): bool
    {
        if (!$this->verifySignature($data)) {
            Log::warning('[Billplz] Invalid X-Signature', $data);
            return false;
        }

        $bill = BillplzBill::where('billplz_id', $data['id'])->first();
        if (!$bill) {
            Log::warning('[Billplz] Bill not found', ['id' => $data['id']]);
            return false;
        }

        $paid   = ($data['paid'] ?? 'false') === 'true';
        $status = $paid ? 'paid' : 'failed';

        $bill->update([
            'status'             => $status,
            'paid_at'            => $paid ? now() : null,
            'paid_amount'        => $data['paid_amount'] ?? null,
            'transaction_id'     => $data['transaction_id'] ?? null,
            'transaction_status' => $data['transaction_status'] ?? null,
            'callback_data'      => $data,
        ]);

        if ($paid && $bill->billable_type === Invoice::class) {
            $this->markInvoicePaid($bill);
        }

        Log::info('[Billplz] Callback processed', [
            'bill_id' => $bill->billplz_id,
            'status'  => $status,
        ]);

        return true;
    }

    // ── Mark Invoice Paid ─────────────────────────────────────────
    private function markInvoicePaid(BillplzBill $bill): void
    {
        $invoice = Invoice::find($bill->billable_id);
        if (!$invoice) return;

        $paidAmount = (float) ($bill->paid_amount ?? $bill->amount) / 100; // cents to MYR
        $newPaid    = (float) $invoice->paid_amount + $paidAmount;
        $newBalance = max(0, (float) $invoice->total - $newPaid);
        $newStatus  = $newBalance <= 0 ? 'paid' : 'partial';

        $invoice->update([
            'paid_amount' => $newPaid,
            'balance_due' => $newBalance,
            'status'      => $newStatus,
        ]);

        Log::info('[Billplz] Invoice marked ' . $newStatus, [
            'invoice_id' => $invoice->id,
            'paid'       => $newPaid,
            'balance'    => $newBalance,
        ]);
    }
}
