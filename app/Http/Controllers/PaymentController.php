<?php

namespace App\Http\Controllers;

use App\Models\BillplzBill;
use App\Models\Invoice;
use App\Services\BillplzService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    // ── Pay Invoice — create bill + redirect ──────────────────────
    public function payInvoice(Invoice $invoice, BillplzService $billplz): RedirectResponse
    {
        // Check existing pending bill
        $existing = BillplzBill::where('billable_type', Invoice::class)
            ->where('billable_id', $invoice->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return redirect($existing->url);
        }

        try {
            $bill = $billplz->createInvoiceBill($invoice);

            // Mock mode — redirect ke mock payment page
            if (config('billplz.mock_mode')) {
                $url = route('billplz.mock.page', $bill->billplz_id);
                return view('payment.redirect', compact('url'));
            }

            $url = $bill->url;
            return view('payment.redirect', compact('url'));
        } catch (\Exception $e) {
            Log::error('[Billplz] createInvoiceBill failed', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);
            return redirect()->back()->with('error', 'Payment gateway error: ' . $e->getMessage());
        }
    }

    // ── Redirect after payment ────────────────────────────────────
    public function redirect(Request $request, Invoice $invoice): RedirectResponse
    {
        $billId = $request->query('billplz[id]') ?? $request->input('billplz.id');
        $paid   = $request->query('billplz[paid]') ?? $request->input('billplz.paid');

        Log::info('[Billplz] Redirect received', [
            'invoice_id' => $invoice->id,
            'bill_id'    => $billId,
            'paid'       => $paid,
        ]);

        if ($paid === 'true') {
            return redirect('/app/invoices/' . $invoice->id)
                ->with('success', 'Pembayaran berjaya! Terima kasih.');
        }

        return redirect('/app/invoices/' . $invoice->id)
            ->with('warning', 'Pembayaran tidak berjaya atau dibatalkan.');
    }

    // ── Callback — Billplz server-to-server ──────────────────────
    public function callback(Request $request, BillplzService $billplz)
    {
        Log::info('[Billplz] Callback received', $request->all());

        $success = $billplz->handleCallback($request->all());

        return response($success ? 'OK' : 'FAILED', $success ? 200 : 400);
    }
    // ── Mock Payment Page ─────────────────────────────────────────────
    public function mockPage(string $billId)
    {
        $bill = BillplzBill::where('billplz_id', $billId)->firstOrFail();

        if ($bill->isPaid()) {
            return redirect('/app')->with('success', 'Bill already paid.');
        }

        return view('payment.mock-billplz', compact('bill'));
    }

    // ── Mock Pay — simulate success/failed ────────────────────────
    public function mockPay(Request $request, string $billId, BillplzService $billplz): RedirectResponse
    {
        $bill     = BillplzBill::where('billplz_id', $billId)->firstOrFail();
        $simulate = $request->input('simulate', 'success');
        $paid     = $simulate === 'success';

        // Build mock callback data
        $callbackData = [
            'id'                 => $billId,
            'collection_id'     => $bill->collection_id,
            'paid'              => $paid ? 'true' : 'false',
            'state'             => $paid ? 'paid' : 'failed',
            'amount'            => (int) round($bill->amount * 100),
            'paid_amount'       => $paid ? (int) round($bill->amount * 100) : 0,
            'due_at'            => now()->format('Y-m-d'),
            'email'             => $bill->payer_email ?? '',
            'mobile'            => $bill->payer_phone ?? '',
            'name'              => $bill->payer_name ?? '',
            'reference_1'       => $bill->reference_no,
            'transaction_id'    => 'MOCK-TXN-' . strtoupper(Str::random(8)),
            'transaction_status'=> $paid ? 'success' : 'failed',
            'x_signature'       => 'MOCK-BYPASS',
        ];

        // Directly update bill (bypass signature verification for mock)
        $bill->update([
            'status'             => $paid ? 'paid' : 'failed',
            'paid_at'            => $paid ? now() : null,
            'paid_amount'        => $callbackData['paid_amount'],
            'transaction_id'     => $callbackData['transaction_id'],
            'transaction_status' => $callbackData['transaction_status'],
            'callback_data'      => $callbackData,
        ]);

        if ($paid && $bill->billable_type === Invoice::class) {
            $invoice    = Invoice::find($bill->billable_id);
            if ($invoice) {
                $paidAmt    = $bill->amount;
                $newPaid    = (float) $invoice->paid_amount + $paidAmt;
                $newBalance = max(0, (float) $invoice->total - $newPaid);
                $newStatus  = $newBalance <= 0 ? 'paid' : 'partial';
                $invoice->update([
                    'paid_amount' => $newPaid,
                    'balance_due' => $newBalance,
                    'status'      => $newStatus,
                ]);

                Log::info('[Billplz Mock] Invoice updated', [
                    'invoice_id' => $invoice->id,
                    'status'     => $newStatus,
                ]);
            }
        }

        $invoiceId = $bill->billable_id;

        if ($paid) {
            return redirect('/app/invoices/' . $invoiceId)
                ->with('success', '[SANDBOX] Pembayaran berjaya disimulasi! Invoice dikemaskini.');
        }

        return redirect('/app/invoices/' . $invoiceId)
            ->with('warning', '[SANDBOX] Pembayaran gagal disimulasi.');
    }
}
