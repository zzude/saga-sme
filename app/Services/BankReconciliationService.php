<?php

namespace App\Services;

use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\JournalLine;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BankReconciliationService
{
    /**
     * Get all GL journal lines for the bank account up to statement date
     */
    public function getGlLines(BankReconciliation $recon): \Illuminate\Support\Collection
    {
        return JournalLine::with(['journal'])
            ->join('journal_headers as jh', 'jh.id', '=', 'journal_lines.journal_header_id')
            ->where('journal_lines.account_id', $recon->account_id)
            ->where('jh.company_id', $recon->company_id)
            ->where('jh.status', 'posted')
            ->where('jh.date', '<=', $recon->statement_date)
            ->select(
                'journal_lines.id',
                'journal_lines.journal_header_id',
                'journal_lines.debit',
                'journal_lines.credit',
                'journal_lines.description',
                'jh.date',
                'jh.reference_no',
            )
            ->orderBy('jh.date')
            ->get();
    }

    /**
     * Get cleared items for this reconciliation
     */
    public function getClearedIds(BankReconciliation $recon): array
    {
        return BankReconciliationItem::where('reconciliation_id', $recon->id)
            ->where('status', 'cleared')
            ->pluck('journal_line_id')
            ->toArray();
    }

    /**
     * Toggle clear/unclear a journal line
     */
    public function toggleItem(BankReconciliation $recon, int $journalLineId): void
    {
        $existing = BankReconciliationItem::where('reconciliation_id', $recon->id)
            ->where('journal_line_id', $journalLineId)
            ->first();

        if ($existing) {
            if ($existing->status === 'cleared') {
                $existing->update(['status' => 'pending', 'cleared_at' => null]);
            } else {
                $existing->update(['status' => 'cleared', 'cleared_at' => now()]);
            }
        } else {
            BankReconciliationItem::create([
                'reconciliation_id' => $recon->id,
                'journal_line_id'   => $journalLineId,
                'source_type'       => 'journal',
                'status'            => 'cleared',
                'cleared_at'        => now(),
            ]);
        }
    }

    /**
     * Calculate cleared book balance
     */
    public function clearedBalance(BankReconciliation $recon): float
    {
        $cleared = BankReconciliationItem::where('reconciliation_id', $recon->id)
            ->where('status', 'cleared')
            ->join('journal_lines', 'journal_lines.id', '=', 'bank_reconciliation_items.journal_line_id')
            ->selectRaw('SUM(journal_lines.debit) - SUM(journal_lines.credit) as net')
            ->value('net');

        return (float) $cleared;
    }

    /**
     * Complete reconciliation
     */
    public function complete(BankReconciliation $recon): void
    {
        $diff = (float) $recon->statement_balance - $this->clearedBalance($recon);

        if (abs($diff) > 0.01) {
            throw new \Exception('Cannot complete — difference is MYR ' . number_format($diff, 2) . '. Must be 0.00.');
        }

        $recon->update([
            'status'       => 'reconciled',
            'completed_by' => Auth::id(),
            'completed_at' => now(),
        ]);
    }
}