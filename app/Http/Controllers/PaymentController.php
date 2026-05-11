<?php

namespace App\Http\Controllers;

use App\Models\BillplzBill;
use App\Models\Invoice;
use App\Services\BillplzService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

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
            return redirect($bill->url);
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
}
