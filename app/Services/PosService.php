<?php

namespace App\Services;

use App\Models\PosSession;
use App\Models\PosTransaction;
use App\Models\PosTransactionItem;
use App\Models\Item;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\JournalHeader;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;

class PosService
{
    public function openSession(float $openingCash = 0): PosSession
    {
        $companyId = auth()->user()->company_id;
        $existing = PosSession::where('company_id', $companyId)
            ->where('user_id', auth()->id())
            ->where('status', 'open')
            ->first();
        if ($existing) return $existing;
        return PosSession::create([
            'company_id'   => $companyId,
            'user_id'      => auth()->id(),
            'opened_at'    => now(),
            'opening_cash' => $openingCash,
            'status'       => 'open',
        ]);
    }

    public function closeSession(PosSession $session, float $closingCash = 0): PosSession
    {
        $session->update([
            'status'       => 'closed',
            'closed_at'    => now(),
            'closing_cash' => $closingCash,
        ]);
        return $session;
    }

    public function processSale(
        PosSession $session,
        array $cartItems,
        string $paymentMethod,
        float $amountTendered,
        ?string $customerName = null,
        ?string $notes = null
    ): PosTransaction {
        return DB::transaction(function () use ($session, $cartItems, $paymentMethod, $amountTendered, $customerName, $notes) {
            $companyId = auth()->user()->company_id;
            $subtotal = $discountAmount = $taxAmount = 0;
            foreach ($cartItems as $cartItem) {
                $gross    = round((float)$cartItem['quantity'] * (float)$cartItem['unit_price'], 2);
                $disc     = round($gross * ((float)($cartItem['discount_percent'] ?? 0) / 100), 2);
                $net      = $gross - $disc;
                $sst      = ($cartItem['is_sst_applicable'] ?? false)
                    ? round($net * ((float)($cartItem['sst_rate'] ?? 8) / 100), 2) : 0;
                $subtotal       += $gross;
                $discountAmount += $disc;
                $taxAmount      += $sst;
            }
            $totalAmount  = round($subtotal - $discountAmount + $taxAmount, 2);
            $changeAmount = round($amountTendered - $totalAmount, 2);

            $transaction = PosTransaction::create([
                'company_id'      => $companyId,
                'session_id'      => $session->id,
                'transaction_no'  => PosTransaction::generateTransactionNo(),
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_amount'      => $taxAmount,
                'total_amount'    => $totalAmount,
                'payment_method'  => $paymentMethod,
                'amount_tendered' => $amountTendered,
                'change_amount'   => max(0, $changeAmount),
                'customer_name'   => $customerName,
                'notes'           => $notes,
                'status'          => 'completed',
                'created_by'      => auth()->id(),
            ]);

            foreach ($cartItems as $cartItem) {
                PosTransactionItem::create([
                    'transaction_id'    => $transaction->id,
                    'item_id'           => $cartItem['item_id'] ?? null,
                    'description'       => $cartItem['description'],
                    'quantity'          => $cartItem['quantity'],
                    'unit_price'        => $cartItem['unit_price'],
                    'discount_percent'  => $cartItem['discount_percent'] ?? 0,
                    'is_sst_applicable' => $cartItem['is_sst_applicable'] ?? false,
                    'sst_rate'          => $cartItem['sst_rate'] ?? 8,
                ]);
                if (!empty($cartItem['item_id'])) {
                    $item = Item::withoutGlobalScope('company')->find($cartItem['item_id']);
                    if ($item && $item->track_inventory) {
                        $item->adjustStock('out', (float)$cartItem['quantity'], (float)$cartItem['unit_price'],
                            PosTransaction::class, $transaction->id, $transaction->transaction_no);
                    }
                }
            }

            $journal = $this->postSaleJournal($transaction, $companyId, $paymentMethod);
            $transaction->update(['journal_id' => $journal->id]);
            $session->increment('total_transactions');
            $session->increment('total_sales', $totalAmount);
            return $transaction;
        });
    }

    public function voidTransaction(PosTransaction $transaction, string $reason): PosTransaction
    {
        if ($transaction->status === 'voided') {
            throw new \RuntimeException('Transaksi ini sudah dibatalkan.');
        }
        return DB::transaction(function () use ($transaction, $reason) {
            foreach ($transaction->items as $item) {
                if ($item->item_id) {
                    $stockItem = Item::withoutGlobalScope('company')->find($item->item_id);
                    if ($stockItem && $stockItem->track_inventory) {
                        $stockItem->adjustStock('in', (float)$item->quantity, (float)$item->unit_price,
                            PosTransaction::class, $transaction->id, 'VOID-'.$transaction->transaction_no);
                    }
                }
            }
            if ($transaction->journal_id) $this->postVoidJournal($transaction);
            $transaction->update([
                'status'      => 'voided',
                'voided_by'   => auth()->id(),
                'voided_at'   => now(),
                'void_reason' => $reason,
            ]);
            $transaction->session->decrement('total_transactions');
            $transaction->session->decrement('total_sales', $transaction->total_amount);
            return $transaction;
        });
    }

    public function getActiveSession(): ?PosSession
    {
        return PosSession::where('company_id', auth()->user()->company_id)
            ->where('user_id', auth()->id())
            ->where('status', 'open')
            ->latest()->first();
    }

    private function getCurrentPeriodId(int $companyId): ?int
    {
        return AccountingPeriod::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('status', 'open')
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->value('id');
    }

    private function postSaleJournal(PosTransaction $transaction, int $companyId, string $paymentMethod): JournalHeader
    {
        $periodId = $this->getCurrentPeriodId($companyId);

        $journal = JournalHeader::create([
            'company_id'   => $companyId,
            'period_id'    => $periodId,
            'reference_no' => $transaction->transaction_no,
            'date'         => now()->toDateString(),
            'summary_text' => 'POS Sale: '.$transaction->transaction_no,
            'source_type'  => 'pos',
            'status'       => 'posted',
            'posted_at'    => now(),
            'created_by'   => auth()->id(),
            'posted_by'    => auth()->id(),
        ]);

        $debitAccountCode = match ($paymentMethod) {
            'cash'  => '1200',
            'card'  => '1100',
            'qr'    => '1100',
            default => '1200',
        };
        $debitAccountId = Account::where('company_id', $companyId)->where('code', $debitAccountCode)->value('id');

        JournalLine::create([
            'journal_header_id' => $journal->id,
            'account_id'        => $debitAccountId,
            'description'       => 'POS: '.$transaction->transaction_no,
            'debit'             => $transaction->total_amount,
            'credit'            => 0,
        ]);

        $salesAccountId = Account::where('company_id', $companyId)->where('code', '4100')->value('id');
        $netSales = $transaction->total_amount - $transaction->tax_amount;

        JournalLine::create([
            'journal_header_id' => $journal->id,
            'account_id'        => $salesAccountId,
            'description'       => 'POS Sales: '.$transaction->transaction_no,
            'debit'             => 0,
            'credit'            => $netSales,
        ]);

        if ($transaction->tax_amount > 0) {
            $sstAccountId = Account::where('company_id', $companyId)->where('code', '2300')->value('id');
                JournalLine::create([
                    'journal_header_id' => $journal->id,
                    'account_id'        => $sstAccountId,
                    'description'       => 'SST: '.$transaction->transaction_no,
                    'debit'             => 0,
                    'credit'            => $transaction->tax_amount,
                ]);
        }

        return $journal;
    }

    private function postVoidJournal(PosTransaction $transaction): void
    {
        $original = $transaction->journal;
        if (!$original) return;
        $companyId = auth()->user()->company_id;
        $periodId  = $this->getCurrentPeriodId($companyId);

        $reversal = JournalHeader::create([
            'company_id'   => $companyId,
            'period_id'    => $periodId,
            'reference_no' => 'VOID-'.$transaction->transaction_no,
            'date'         => now()->toDateString(),
            'summary_text' => 'VOID POS: '.$transaction->transaction_no,
            'source_type'  => 'pos',
            'status'       => 'posted',
            'posted_at'    => now(),
            'created_by'   => auth()->id(),
            'posted_by'    => auth()->id(),
        ]);

        foreach ($original->lines as $line) {
            JournalLine::create([
                'journal_header_id' => $reversal->id,
                'account_id'        => $line->account_id,
                'description'       => 'VOID: '.$line->description,
                'debit'             => $line->credit,
                'credit'            => $line->debit,
            ]);
        }
    }
}