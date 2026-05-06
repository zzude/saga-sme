<?php

namespace App\Services;

use App\Models\StaffLoan;
use App\Models\StaffLoanRepayment;
use App\Models\JournalHeader;
use App\Models\JournalLine;
use App\Models\AccountingPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class StaffLoanService
{
    // ── Generate Loan No ───────────────────────────────────

    public function generateLoanNo(int $companyId): string
    {
        $prefix = 'LN-' . date('Ym') . '-';

        $last = StaffLoan::where('company_id', $companyId)
            ->where('loan_no', 'like', $prefix . '%')
            ->orderByDesc('loan_no')
            ->value('loan_no');

        $seq = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // ── Approve Loan ───────────────────────────────────────

    public function approve(StaffLoan $loan, float $amountApproved, int $approvedBy): void
    {
        $loan->update([
            'status'          => 'approved',
            'amount_approved' => $amountApproved,
            'approved_date'   => today(),
            'approved_by'     => $approvedBy,
        ]);
    }

    // ── Disburse Loan + Generate Schedule ─────────────────

    public function disburse(StaffLoan $loan): void
    {
        DB::transaction(function () use ($loan) {

            // 1. Post disbursement journal
            $journal = $this->postDisbursementJournal($loan);

            // 2. Generate repayment schedule
            $this->generateRepaymentSchedule($loan);

            // 3. Update loan status
            $loan->update([
                'status'        => 'disbursed',
                'disbursed_date'=> today(),
                'journal_id'    => $journal->id,
            ]);
        });
    }

    // ── Disbursement Journal ───────────────────────────────

    private function postDisbursementJournal(StaffLoan $loan): JournalHeader
    {
        $journal = JournalHeader::create([
                        'reference_no' => $this->generateJournalNo($loan->company_id),
            'source_type'  => 'manual',
            'date'        => today(),
            'period_id'    => AccountingPeriod::where('company_id', $loan->company_id)->where('status', 'open')->value('id'),
            'summary_text' => 'Staff Loan Disbursement - ' . $loan->loan_no . ' (' . $loan->employee->name . ')',
            'status'      => 'posted',
            'created_by'  => auth()->id(),
        ]);

        // DR Staff Loans Receivable (1180)
        JournalLine::create([
                        'journal_header_id' => $journal->id,
            'account_id'     => $loan->account_id,
            'description'    => 'Loan disbursed to ' . $loan->employee->name,
            'debit'          => $loan->amount_approved,
            'credit'         => 0,
        ]);

        // CR Bank / Disbursement Account
        JournalLine::create([
                        'journal_header_id' => $journal->id,
            'account_id'     => $loan->disbursement_account_id,
            'description'    => 'Loan disbursed - ' . $loan->loan_no,
            'debit'          => 0,
            'credit'         => $loan->amount_approved,
        ]);

        return $journal;
    }

    // ── Generate Repayment Schedule ────────────────────────

    public function generateRepaymentSchedule(StaffLoan $loan): void
    {
        $loan->repayments()->delete(); // Clear existing if regenerate

        $principal    = (float) $loan->amount_approved;
        $rate         = (float) $loan->interest_rate;
        $tenure       = (int) $loan->tenure_months;
        $monthlyRate  = $rate / 100 / 12;

        // Monthly instalment (flat if rate=0, reducing balance if rate>0)
        if ($monthlyRate > 0) {
            $monthlyTotal = $principal * ($monthlyRate * pow(1 + $monthlyRate, $tenure))
                          / (pow(1 + $monthlyRate, $tenure) - 1);
        } else {
            $monthlyTotal = round($principal / $tenure, 2);
        }

        $balance = $principal;
        $startDate = Carbon::parse($loan->disbursed_date ?? today());

        for ($i = 1; $i <= $tenure; $i++) {
            $interest  = round($balance * $monthlyRate, 2);
            $principal_portion = round($monthlyTotal - $interest, 2);

            // Last installment — absorb rounding diff
            if ($i === $tenure) {
                $principal_portion = $balance;
                $monthlyTotal      = $balance + $interest;
            }

            $balance -= $principal_portion;

            StaffLoanRepayment::create([
                'company_id'       => $loan->company_id,
                'staff_loan_id'    => $loan->id,
                'installment_no'   => $i,
                'due_date'         => $startDate->copy()->addMonths($i),
                'principal_amount' => $principal_portion,
                'interest_amount'  => $interest,
                'total_amount'     => $monthlyTotal,
                'balance_after'    => round($balance, 2),
                'status'           => 'pending',
            ]);
        }
    }

    // ── Mark Repayment Paid ────────────────────────────────

    public function markRepaymentPaid(StaffLoanRepayment $repayment): void
    {
        DB::transaction(function () use ($repayment) {
            $loan = $repayment->loan;

            // Post repayment journal
            $journal = $this->postRepaymentJournal($repayment);

            // Update repayment
            $repayment->update([
                'status'     => 'paid',
                'paid_amount'=> $repayment->total_amount,
                'paid_date'  => today(),
                'journal_id' => $journal->id,
            ]);

            // Check if fully settled
            $pendingCount = $loan->repayments()->where('status', '!=', 'paid')->count();
            if ($pendingCount === 0) {
                $loan->update(['status' => 'settled']);
            }
        });
    }

    // ── Repayment Journal ──────────────────────────────────

    private function postRepaymentJournal(StaffLoanRepayment $repayment): JournalHeader
    {
        $loan = $repayment->loan;

        $journal = JournalHeader::create([
                        'reference_no' => $this->generateJournalNo($loan->company_id),
            'source_type'  => 'manual',
            'date'        => today(),
            'period_id'    => AccountingPeriod::where('company_id', $loan->company_id)->where('status', 'open')->value('id'),
            'summary_text' => 'Loan Repayment - ' . $loan->loan_no . ' Instalment #' . $repayment->installment_no,
            'status'      => 'posted',
            'created_by'  => auth()->id(),
        ]);

        // DR Salary Payable / Bank (2120)
        JournalLine::create([
                        'journal_header_id' => $journal->id,
            'account_id'  => $loan->disbursement_account_id,
            'summary_text' => 'Loan repayment - ' . $loan->employee->name . ' Inst#' . $repayment->installment_no,
            'debit'       => $repayment->total_amount,
            'credit'      => 0,
        ]);

        // CR Staff Loans Receivable (1180)
        JournalLine::create([
                        'journal_header_id' => $journal->id,
            'account_id'  => $loan->account_id,
            'summary_text' => 'Loan repayment - ' . $loan->loan_no . ' Inst#' . $repayment->installment_no,
            'debit'       => 0,
            'credit'      => $repayment->total_amount,
        ]);

        return $journal;
    }

    // ── Helpers ────────────────────────────────────────────

    private function generateJournalNo(int $companyId): string
    {
        $prefix = 'JV-' . date('Ym') . '-';
        $last   = JournalHeader::where('company_id', $companyId)
            ->where('reference_no', 'like', $prefix . '%')
            ->orderByDesc('reference_no')
            ->value('reference_no');
        $seq = $last ? (int) substr($last, -4) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
