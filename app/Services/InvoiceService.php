<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Account;
use App\Models\JournalHeader;
use App\Models\JournalLine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\InvoicePayment;
use App\Jobs\SubmitInvoiceJob;
use App\Services\PlanService;

class InvoiceService
{
    public function post(Invoice $invoice): void
    {
        // Plan limit check
        $planService = app(PlanService::class);
        if (!$planService->canCreateInvoice($invoice->company)) {
            $usage = $planService->getUsage($invoice->company);
            throw new \Exception(
                'Had invois bulanan dicapai (' . $usage['invoices_month']['used'] . '/' . $usage['invoices_month']['limit'] . '). Naik taraf plan untuk teruskan.'
            );
        }

        if (!$invoice->isDraft() && $invoice->status !== 'sent') {
            throw new \Exception('Only draft or sent invoices can be posted.');
        }

        // Check if already posted
        $existing = JournalHeader::where('company_id', $invoice->company_id)
            ->where('reference_no', $invoice->invoice_no)
            ->first();

        if ($existing) {
            throw new \Exception('Invoice already posted to GL.');
        }

        // ── FX: validate rate present ─────────────────────────────────────
        $rate = (float) ($invoice->exchange_rate ?? 1.0);
        if ($rate <= 0) {
            throw new \Exception('Invalid exchange rate. Please set a valid rate before posting.');
        }

        // Find AR account (1300-level asset Receivable)
        $arAccount = Account::where('company_id', $invoice->company_id)
            ->where('type', 'asset')
            ->where('level', 3)
            ->where('name', 'like', '%Receivable%')
            ->first();

        if (!$arAccount) {
            throw new \Exception('Accounts Receivable account not found. Please create an asset account with "Receivable" in the name.');
        }

        DB::transaction(function () use ($invoice, $arAccount, $rate) {

            // ── Step 1: Compute base_* on each line (Option B — no double rounding) ──
            $invoice->load('lines');
            foreach ($invoice->lines as $line) {
                $line->foreign_unit_price = $line->unit_price;
                $line->foreign_line_total = $line->line_total;
                $line->base_unit_price    = round((float) $line->unit_price * $rate, 2);  // informational
                $line->base_line_total    = round((float) $line->line_total * $rate, 2);  // GL authoritative
                $line->save();
            }

            // ── Step 2: Compute base_* on invoice header ──────────────────
            $baseSubtotal = $invoice->lines->sum('base_line_total');
            // Tax converted separately — consistent with line-level rounding
            $baseTax      = round((float) $invoice->tax_amount * $rate, 2);
            $baseTotal    = round($baseSubtotal + $baseTax, 2);

            // Store foreign totals snapshot
            $invoice->foreign_subtotal = $invoice->subtotal;
            $invoice->foreign_tax      = $invoice->tax_amount;
            $invoice->foreign_total    = $invoice->total;

            // Store MYR base totals (immutable after posting)
            $invoice->base_subtotal = $baseSubtotal;
            $invoice->base_tax      = $baseTax;
            $invoice->base_total    = $baseTotal;
            $invoice->save();

            // ── Step 3: Create Journal Header (MYR only) ──────────────────
            $currencyCode = $invoice->currency_code ?? 'MYR';

            $journal = JournalHeader::create([
                'company_id'             => $invoice->company_id,
                'period_id'              => $invoice->period_id,
                'reference_no'           => $invoice->invoice_no,
                'date'                   => $invoice->date,
                'status'                 => 'posted',
                'source_type'  => 'manual',
                'summary_text'           => 'Invoice ' . $invoice->invoice_no
                                            . ' — ' . $invoice->customer->name
                                            . ($currencyCode !== 'MYR' ? " ({$currencyCode} @ {$rate})" : ''),
                // FX metadata — informational, GL lines are always MYR
                'exchange_rate'          => $rate,
                'original_currency_code' => $currencyCode,
                'created_by'             => Auth::id(),
                'posted_by'              => Auth::id(),
                'posted_at'              => now(),
            ]);

            // ── Step 4: GL Lines — ALL in MYR (base_* amounts) ───────────

            // DR Accounts Receivable (base_total)
            JournalLine::create([
                'journal_header_id' => $journal->id,
                'account_id'        => $arAccount->id,
                'debit'             => $baseTotal,
                'credit'            => 0,
                'description'       => 'AR — ' . $invoice->invoice_no
                                        . ($currencyCode !== 'MYR' ? " ({$currencyCode} {$invoice->total} @ {$rate})" : ''),
            ]);

            // CR Revenue accounts per line (base_line_total)
            foreach ($invoice->lines as $line) {
                JournalLine::create([
                    'journal_header_id' => $journal->id,
                    'account_id'        => $line->account_id,
                    'debit'             => 0,
                    'credit'            => $line->base_line_total,
                    'description'       => $line->description
                                            . ($currencyCode !== 'MYR' ? " ({$currencyCode} {$line->line_total})" : ''),
                ]);
            }

            // CR SST Payable — if tax exists
            if ($baseTax > 0) {
                $sstAccount = Account::where('company_id', $invoice->company_id)
                    ->where('type', 'liability')
                    ->where('name', 'like', '%SST%')
                    ->orWhere('name', 'like', '%Tax Payable%')
                    ->where('company_id', $invoice->company_id)
                    ->first();

                if ($sstAccount) {
                    JournalLine::create([
                        'journal_header_id' => $journal->id,
                        'account_id'        => $sstAccount->id,
                        'debit'             => 0,
                        'credit'            => $baseTax,
                        'description'       => 'SST — ' . $invoice->invoice_no,
                    ]);
                }
                // Note: if no SST account found, tax is already included in line CR above
                // This handles cases where tax is embedded in line_total
            }

            // ── Step 5: Lock invoice fields + update status ───────────────
            $invoice->update([
                'status'    => 'sent',
                'posted_at' => now(),
            ]);
        });

        // MyInvois — B2B only
        if (! $invoice->customer->is_individual) {
            SubmitInvoiceJob::dispatch($invoice)->onQueue('default');
        }
    }

    public function void(Invoice $invoice, string $reason): void
    {
        if (!in_array($invoice->status, ['sent', 'partial'])) {
            throw new \Exception('Only sent or partial invoices can be voided.');
        }

        DB::transaction(function () use ($invoice, $reason) {
            $originalJournal = JournalHeader::where('reference_no', $invoice->invoice_no)
                ->where('company_id', $invoice->company_id)
                ->first();

            if ($originalJournal) {
                $originalJournal->update([
                    'status'      => 'voided',
                    'voided_by'   => Auth::id(),
                    'voided_at'   => now(),
                    'void_reason' => $reason,
                ]);
            }

            $invoice->update(['status' => 'void']);
        });
    }

    public function recordPayment(Invoice $invoice, array $data): void
    {
        if (!in_array($invoice->status, ['sent', 'partial'])) {
            throw new \Exception('Only sent or partial invoices can receive payment.');
        }

        $paymentAmount = (float) $data['amount'];

        if ($paymentAmount <= 0) {
            throw new \Exception('Payment amount must be greater than zero.');
        }

        if ($paymentAmount > (float) $invoice->balance_due) {
            throw new \Exception('Payment amount cannot exceed balance due of MYR ' . number_format($invoice->balance_due, 2));
        }

        $arAccount = Account::where('company_id', $invoice->company_id)
            ->where('type', 'asset')
            ->where('level', 3)
            ->where('name', 'like', '%Receivable%')
            ->first();

        if (!$arAccount) {
            throw new \Exception('Accounts Receivable account not found.');
        }

        $bankAccount = Account::where('company_id', $invoice->company_id)
            ->where('id', $data['bank_account_id'])
            ->first();

        if (!$bankAccount) {
            throw new \Exception('Bank account not found.');
        }

        DB::transaction(function () use ($invoice, $data, $paymentAmount, $arAccount, $bankAccount) {

            $journal = JournalHeader::create([
                'company_id'   => $invoice->company_id,
                'period_id'    => $invoice->period_id,
                'reference_no' => 'PMT-' . $invoice->invoice_no,
                'date'         => $data['payment_date'],
                'status'       => 'posted',
                'source_type'  => 'manual',
                'summary_text' => 'Payment — ' . $invoice->invoice_no . ' — ' . $invoice->customer->name,
                'created_by'   => Auth::id(),
                'posted_by'    => Auth::id(),
                'posted_at'    => now(),
            ]);

            JournalLine::create([
                'journal_header_id' => $journal->id,
                'account_id'        => $bankAccount->id,
                'debit'             => $paymentAmount,
                'credit'            => 0,
                'description'       => 'Payment received — ' . $invoice->invoice_no,
            ]);

            JournalLine::create([
                'journal_header_id' => $journal->id,
                'account_id'        => $arAccount->id,
                'debit'             => 0,
                'credit'            => $paymentAmount,
                'description'       => 'AR settled — ' . $invoice->invoice_no,
            ]);

            InvoicePayment::create([
                'company_id'        => $invoice->company_id,
                'invoice_id'        => $invoice->id,
                'payment_date'      => $data['payment_date'],
                'amount'            => $paymentAmount,
                'payment_method'    => $data['payment_method'],
                'reference_no'      => $data['reference_no'] ?? null,
                'bank_account_id'   => $bankAccount->id,
                'journal_header_id' => $journal->id,
                'remarks'           => $data['remarks'] ?? null,
                'received_by'       => Auth::user()->name,
            ]);

            $newPaid    = (float) $invoice->paid_amount + $paymentAmount;
            $newBalance = (float) $invoice->total - $newPaid;
            $newStatus  = $newBalance <= 0 ? 'paid' : 'partial';

            $invoice->update([
                'paid_amount' => $newPaid,
                'balance_due' => $newBalance,
                'status'      => $newStatus,
            ]);
        });
    }
}
