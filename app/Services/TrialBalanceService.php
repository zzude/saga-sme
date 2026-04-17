<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountingPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrialBalanceService
{
    /**
     * Generate trial balance for a given period (or up to a date).
     * Returns collection of rows with: code, name, type, debit, credit, balance
     */
    public function generate(int $companyId, ?int $periodId = null, ?string $asOfDate = null): array
    {
        // Build the journal lines query
        $query = DB::table('journal_lines as jl')
            ->join('journal_headers as jh', 'jh.id', '=', 'jl.journal_header_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jh.company_id', $companyId)
            ->where('jh.status', 'posted')
            ->where('a.level', 3) // postable accounts only
            ->select(
                'a.id as account_id',
                'a.code',
                'a.name',
                'a.type',
                DB::raw('SUM(jl.debit) as total_debit'),
                DB::raw('SUM(jl.credit) as total_credit'),
            )
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type');

        if ($periodId) {
            $query->where('jh.period_id', $periodId);
        }

        if ($asOfDate) {
            $query->where('jh.date', '<=', $asOfDate);
        }

        $rows = $query->orderBy('a.code')->get();

        $totalDebit  = 0;
        $totalCredit = 0;
        $lines       = [];

        foreach ($rows as $row) {
            $debit  = (float) $row->total_debit;
            $credit = (float) $row->total_credit;

            // Net balance based on normal balance side
            $normalBalance = in_array($row->type, ['asset', 'expense']) ? 'debit' : 'credit';
            $balance = $normalBalance === 'debit'
                ? $debit - $credit
                : $credit - $debit;

            $lines[] = [
                'account_id' => $row->account_id,
                'code'       => $row->code,
                'name'       => $row->name,
                'type'       => $row->type,
                'debit'      => $debit,
                'credit'     => $credit,
                'balance'    => $balance,
            ];

            $totalDebit  += $debit;
            $totalCredit += $credit;
        }

        return [
            'lines'        => $lines,
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced'  => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }
}