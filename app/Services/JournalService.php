<?php

namespace App\Services;

use App\Models\AccountingPeriod;
use App\Models\JournalHeader;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalService
{
    /**
     * Post a draft journal — validate balance, lock period, then mark posted.
     */
    public function post(JournalHeader $journal): JournalHeader
    {
        // 1. Must be draft
        if (!$journal->isDraft()) {
            throw ValidationException::withMessages([
                'status' => 'Hanya journal berstatus Draft boleh di-post.',
            ]);
        }

        // 2. Period must be open
        $this->assertPeriodOpen($journal->period);

        // 3. Must be balanced
        if (!$journal->isBalanced()) {
            throw ValidationException::withMessages([
                'balance' => 'Journal tidak balanced — jumlah Debit mesti sama dengan Kredit.',
            ]);
        }

        // 4. Must have at least 2 lines
        if ($journal->lines->count() < 2) {
            throw ValidationException::withMessages([
                'lines' => 'Journal mesti ada sekurang-kurangnya 2 baris.',
            ]);
        }

        DB::transaction(function () use ($journal) {
            $journal->update([
                'status'    => 'posted',
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);
        });

        return $journal->fresh();
    }

    /**
     * Void a posted journal — create reversal entry.
     */
    public function void(JournalHeader $journal, string $reason): JournalHeader
    {
        // 1. Must be posted
        if (!$journal->isPosted()) {
            throw ValidationException::withMessages([
                'status' => 'Hanya journal berstatus Posted boleh di-void.',
            ]);
        }

        // 2. Period must be open
        $this->assertPeriodOpen($journal->period);

        // 3. Reason wajib
        if (empty(trim($reason))) {
            throw ValidationException::withMessages([
                'void_reason' => 'Sebab void mesti diisi.',
            ]);
        }

        DB::transaction(function () use ($journal, $reason) {
            // Mark original as voided
            $journal->update([
                'status'      => 'voided',
                'voided_by'   => auth()->id(),
                'voided_at'   => now(),
                'void_reason' => $reason,
            ]);

            // Create reversal journal
            $reversal = JournalHeader::create([
                'company_id'        => $journal->company_id,
                'period_id'         => $journal->period_id,
                'reference_no'      => $this->generateReferenceNo($journal->company_id),
                'date'              => now()->toDateString(),
                'status'            => 'posted',
                'source_type'       => 'reversal',
                'summary_text'      => 'Reversal: ' . $journal->summary_text,
                'created_by'        => auth()->id(),
                'posted_by'         => auth()->id(),
                'posted_at'         => now(),
                'reversed_from_id'  => $journal->id,
            ]);

            // Reverse all lines (swap debit/credit)
            foreach ($journal->lines as $line) {
                JournalLine::create([
                    'journal_header_id' => $reversal->id,
                    'account_id'        => $line->account_id,
                    'debit'             => $line->credit,
                    'credit'            => $line->debit,
                    'description'       => 'Reversal: ' . $line->description,
                ]);
            }
        });

        return $journal->fresh();
    }

    /**
     * Create a new draft journal with lines.
     *
     * $data = [
     *   'period_id'    => int,
     *   'date'         => string,
     *   'source_type'  => string,
     *   'summary_text' => string,
     *   'lines'        => [
     *     ['account_id' => int, 'debit' => float, 'credit' => float, 'description' => string],
     *     ...
     *   ]
     * ]
     */
    public function create(array $data): JournalHeader
    {
        $this->validateLines($data['lines'] ?? []);

        return DB::transaction(function () use ($data) {
            $journal = JournalHeader::create([
                'company_id'   => auth()->user()->company_id,
                'period_id'    => $data['period_id'],
                'reference_no' => $this->generateReferenceNo(auth()->user()->company_id),
                'date'         => $data['date'],
                'status'       => 'draft',
                'source_type'  => $data['source_type'] ?? 'manual',
                'summary_text' => $data['summary_text'],
                'created_by'   => auth()->id(),
            ]);

            foreach ($data['lines'] as $line) {
                JournalLine::create([
                    'journal_header_id' => $journal->id,
                    'account_id'        => $line['account_id'],
                    'debit'             => $line['debit'] ?? 0,
                    'credit'            => $line['credit'] ?? 0,
                    'description'       => $line['description'] ?? null,
                ]);
            }

            return $journal->fresh(['lines']);
        });
    }

    // ── Private Helpers ────────────────────────────────────────

    private function assertPeriodOpen(AccountingPeriod $period): void
    {
        if (!$period->isOpen()) {
            throw ValidationException::withMessages([
                'period' => "Period '{$period->name}' tidak boleh digunakan — status: {$period->status}.",
            ]);
        }
    }

    private function validateLines(array $lines): void
    {
        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'lines' => 'Journal mesti ada sekurang-kurangnya 2 baris.',
            ]);
        }

        foreach ($lines as $i => $line) {
            if (($line['debit'] ?? 0) > 0 && ($line['credit'] ?? 0) > 0) {
                throw ValidationException::withMessages([
                    "lines.{$i}" => 'Satu baris tidak boleh ada Debit DAN Kredit serentak.',
                ]);
            }
        }

        $totalDebit  = collect($lines)->sum('debit');
        $totalCredit = collect($lines)->sum('credit');

        if (abs($totalDebit - $totalCredit) >= 0.01) {
            throw ValidationException::withMessages([
                'balance' => "Journal tidak balanced — Debit: {$totalDebit}, Kredit: {$totalCredit}.",
            ]);
        }
    }

    private function generateReferenceNo(int $companyId): string
    {
        $year  = now()->format('Y');
        $month = now()->format('m');
        $count = JournalHeader::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->count() + 1;

        return 'JV-' . $year . $month . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        // contoh: JV-202604-0001
    }
}