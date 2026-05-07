<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\CashAdvance;
use App\Models\JournalHeader;
use App\Models\JournalLine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashAdvanceService
{
    public function approve(CashAdvance $advance, float $amountApproved): void
    {
        DB::transaction(function () use ($advance, $amountApproved) {
            $advance->update([
                'status'          => 'approved',
                'amount_approved' => $amountApproved,
                'approved_date'   => now(),
                'approved_by'     => Auth::id(),
            ]);
        });
    }

    public function disburse(CashAdvance $advance, int $bankAccountId): void
    {
        DB::transaction(function () use ($advance, $bankAccountId) {
            $period = AccountingPeriod::where('company_id', $advance->company_id)
                ->where('status', 'open')
                ->latest()
                ->firstOrFail();

            $advanceAccount = Account::where('company_id', $advance->company_id)
                ->where('code', '1190')
                ->firstOrFail();

            $journal = JournalHeader::create([
                'company_id'  => $advance->company_id,
                'period_id'   => $period->id,
                'reference_no' => 'CA-DISB-' . $advance->advance_no,
                'summary_text'  => 'Cash Advance Disbursement: ' . $advance->advance_no,
                'date'        => now(),
                'status'      => 'posted',
                'created_by'  => Auth::id(),
            ]);

            // DR 1190 Cash Advance Receivable
            JournalLine::create([
                'journal_header_id' => $journal->id,
                'account_id'  => $advanceAccount->id,
                'debit'       => $advance->amount_approved,
                'credit'      => 0,
                'description' => 'Cash Advance: ' . $advance->advance_no,
            ]);

            // CR Bank / Cash
            JournalLine::create([
                'journal_header_id' => $journal->id,
                'account_id'  => $bankAccountId,
                'debit'       => 0,
                'credit'      => $advance->amount_approved,
                'description' => 'Cash Advance: ' . $advance->advance_no,
            ]);

            $advance->update([
                'status'         => 'disbursed',
                'disbursed_date' => now(),
                'disbursed_by'   => Auth::id(),
                'account_id'     => $advanceAccount->id,
                'journal_id'     => $journal->id, // cash_advances FK
            ]);
        });
    }

    public function settle(CashAdvance $advance, float $amount, string $type, int $expenseAccountId, string $reference = null): void
    {
        DB::transaction(function () use ($advance, $amount, $type, $expenseAccountId, $reference) {
            $period = AccountingPeriod::where('company_id', $advance->company_id)
                ->where('status', 'open')
                ->latest()
                ->firstOrFail();

            $advanceAccount = Account::where('company_id', $advance->company_id)
                ->where('code', '1190')
                ->firstOrFail();

            $journal = JournalHeader::create([
                'company_id'  => $advance->company_id,
                'period_id'   => $period->id,
                'reference_no' => 'CA-SETL-' . $advance->advance_no,
                'summary_text'  => 'Cash Advance Settlement: ' . $advance->advance_no,
                'date'        => now(),
                'status'      => 'posted',
                'created_by'  => Auth::id(),
            ]);

            // DR Expense / Bank (depends on type)
            JournalLine::create([
                'journal_header_id' => $journal->id,
                'account_id'  => $expenseAccountId,
                'debit'       => $amount,
                'credit'      => 0,
                'description' => 'Settlement: ' . $advance->advance_no,
            ]);

            // CR 1190 Cash Advance Receivable
            JournalLine::create([
                'journal_header_id' => $journal->id,
                'account_id'  => $advanceAccount->id,
                'debit'       => 0,
                'credit'      => $amount,
                'description' => 'Settlement: ' . $advance->advance_no,
            ]);

            $newSettled = (float) $advance->amount_settled + $amount;
            $approved   = (float) $advance->amount_approved;
            $newStatus  = $newSettled >= $approved ? 'settled' : 'disbursed';

            $advance->settlements()->create([
                'company_id'      => $advance->company_id,
                'settlement_type' => $type,
                'amount'          => $amount,
                'settlement_date' => now(),
                'reference_no'    => $reference,
                'journal_id'      => $journal->id,
                'created_by'      => Auth::id(),
            ]);

            $advance->update([
                'amount_settled' => $newSettled,
                'status'         => $newStatus,
            ]);
        });
    }
}
