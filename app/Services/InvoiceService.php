<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Account;
use App\Models\JournalHeader;
use App\Models\JournalLine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function post(Invoice $invoice): void
    {
        if (!$invoice->isDraft() && $invoice->status !== 'sent') {
            throw new \Exception('Only draft or sent invoices can be posted.');
        }

        // Find AR account (1300)
        $arAccount = Account::where('company_id', $invoice->company_id)
            ->where('type', 'asset')
            ->where('level', 3)
            ->where('name', 'like', '%Receivable%')
            ->first();

        if (!$arAccount) {
            throw new \Exception('Accounts Receivable account not found. Please create an asset account with "Receivable" in the name.');
        }

        DB::transaction(function () use ($invoice, $arAccount) {

            // Create Journal Header
            $journal = JournalHeader::create([
                'company_id'   => $invoice->company_id,
                'period_id'    => $invoice->period_id,
                'reference_no' => $invoice->invoice_no,
                'date'         => $invoice->date,
                'status'       => 'posted',
                'source_type'  => 'manual',
                'summary_text' => 'Invoice ' . $invoice->invoice_no . ' — ' . $invoice->customer->name,
                'created_by'   => Auth::id(),
                'posted_by'    => Auth::id(),
                'posted_at'    => now(),
            ]);

            // DR Accounts Receivable
            JournalLine::create([
                'journal_header_id' => $journal->id,
                'account_id'        => $arAccount->id,
                'debit'             => $invoice->total,
                'credit'            => 0,
                'description'       => 'AR — ' . $invoice->invoice_no,
            ]);

            // CR Revenue accounts (per line)
            foreach ($invoice->lines as $line) {
                JournalLine::create([
                    'journal_header_id' => $journal->id,
                    'account_id'        => $line->account_id,
                    'debit'             => 0,
                    'credit'            => $line->line_total,
                    'description'       => $line->description,
                ]);
            }

            // Update invoice status
            $invoice->update([
                'status'    => 'sent',
                'posted_at' => now(),
            ]);
        });
    }

    public function void(Invoice $invoice, string $reason): void
    {
        if (!in_array($invoice->status, ['sent', 'partial'])) {
            throw new \Exception('Only sent or partial invoices can be voided.');
        }

        DB::transaction(function () use ($invoice, $reason) {
            // Reverse the journal
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
}