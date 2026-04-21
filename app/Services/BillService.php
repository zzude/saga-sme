<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\JournalHeader;
use App\Models\JournalLine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BillService
{
    public function post(Bill $bill): void
    {
        if (!in_array($bill->status, ['draft', 'submitted'])) {
            throw new \Exception('Only draft or submitted bills can be posted.');
        }

        // Find AP account
        $apAccount = Account::where('company_id', $bill->company_id)
            ->where('type', 'liability')
            ->where('level', 3)
            ->where('name', 'like', '%Payable%')
            ->first();

        if (!$apAccount) {
            throw new \Exception('Accounts Payable account not found. Please create a liability account with "Payable" in the name.');
        }

        DB::transaction(function () use ($bill, $apAccount) {

            // Create Journal Header
            $journal = JournalHeader::create([
                'company_id'   => $bill->company_id,
                'period_id'    => $bill->period_id,
                'reference_no' => $bill->bill_no,
                'date'         => $bill->date,
                'status'       => 'posted',
                'source_type'  => 'manual',
                'summary_text' => 'Bill ' . $bill->bill_no . ' — ' . $bill->vendor->name,
                'created_by'   => Auth::id(),
                'posted_by'    => Auth::id(),
                'posted_at'    => now(),
            ]);

            // DR Expense accounts (per line)
            foreach ($bill->lines as $line) {
                JournalLine::create([
                    'journal_header_id' => $journal->id,
                    'account_id'        => $line->account_id,
                    'debit'             => $line->line_total,
                    'credit'            => 0,
                    'description'       => $line->description,
                ]);
            }

            // CR Accounts Payable
            JournalLine::create([
                'journal_header_id' => $journal->id,
                'account_id'        => $apAccount->id,
                'debit'             => 0,
                'credit'            => $bill->total,
                'description'       => 'AP — ' . $bill->bill_no,
            ]);

            // Update bill
            $bill->update([
                'status'            => 'approved',
                'posted_at'         => now(),
                'approved_by'       => Auth::id(),
                'approved_at'       => now(),
                'journal_header_id' => $journal->id,
            ]);
        });
    }

    public function recordPayment(Bill $bill, array $data): void
    {
        if (!in_array($bill->status, ['approved', 'partial'])) {
            throw new \Exception('Only approved or partial bills can receive payment.');
        }

        $paymentAmount = (float) $data['amount'];

        if ($paymentAmount <= 0) {
            throw new \Exception('Payment amount must be greater than zero.');
        }

        if ($paymentAmount > (float) $bill->balance_due) {
            throw new \Exception('Payment cannot exceed balance due of MYR ' . number_format($bill->balance_due, 2));
        }

        $apAccount = Account::where('company_id', $bill->company_id)
            ->where('type', 'liability')
            ->where('level', 3)
            ->where('name', 'like', '%Payable%')
            ->first();

        if (!$apAccount) {
            throw new \Exception('Accounts Payable account not found.');
        }

        $bankAccount = Account::where('company_id', $bill->company_id)
            ->where('id', $data['bank_account_id'])
            ->first();

        if (!$bankAccount) {
            throw new \Exception('Bank account not found.');
        }

        DB::transaction(function () use ($bill, $data, $paymentAmount, $apAccount, $bankAccount) {

            // Create Journal
            $journal = JournalHeader::create([
                'company_id'   => $bill->company_id,
                'period_id'    => $bill->period_id,
                'reference_no' => 'PMT-' . $bill->bill_no,
                'date'         => $data['payment_date'],
                'status'       => 'posted',
                'source_type'  => 'manual',
                'summary_text' => 'Payment — ' . $bill->bill_no . ' — ' . $bill->vendor->name,
                'created_by'   => Auth::id(),
                'posted_by'    => Auth::id(),
                'posted_at'    => now(),
            ]);

            // DR Accounts Payable
            JournalLine::create([
                'journal_header_id' => $journal->id,
                'account_id'        => $apAccount->id,
                'debit'             => $paymentAmount,
                'credit'            => 0,
                'description'       => 'AP settled — ' . $bill->bill_no,
            ]);

            // CR Bank
            JournalLine::create([
                'journal_header_id' => $journal->id,
                'account_id'        => $bankAccount->id,
                'debit'             => 0,
                'credit'            => $paymentAmount,
                'description'       => 'Payment — ' . $bill->bill_no,
            ]);

            // Record payment
            BillPayment::create([
                'company_id'        => $bill->company_id,
                'bill_id'           => $bill->id,
                'payment_date'      => $data['payment_date'],
                'amount'            => $paymentAmount,
                'payment_method'    => $data['payment_method'],
                'reference_no'      => $data['reference_no'] ?? null,
                'bank_account_id'   => $bankAccount->id,
                'journal_header_id' => $journal->id,
                'remarks'           => $data['remarks'] ?? null,
                'paid_by'           => Auth::user()->name,
            ]);

            // Update bill
            $newPaid    = (float) $bill->paid_amount + $paymentAmount;
            $newBalance = (float) $bill->total - $newPaid;
            $newStatus  = $newBalance <= 0 ? 'paid' : 'partial';

            $bill->update([
                'paid_amount' => $newPaid,
                'balance_due' => $newBalance,
                'status'      => $newStatus,
            ]);
        });
    }

    public function void(Bill $bill, string $reason): void
    {
        if (!in_array($bill->status, ['approved', 'partial'])) {
            throw new \Exception('Only approved or partial bills can be voided.');
        }

        DB::transaction(function () use ($bill, $reason) {
            if ($bill->journal_header_id) {
                $journal = JournalHeader::find($bill->journal_header_id);
                if ($journal) {
                    $journal->update([
                        'status'      => 'voided',
                        'voided_by'   => Auth::id(),
                        'voided_at'   => now(),
                        'void_reason' => $reason,
                    ]);
                }
            }

            $bill->update([
                'status'     => 'void',
                'voided_by'  => Auth::id(),
                'voided_at'  => now(),
                'void_reason' => $reason,
            ]);
        });
    }
}