<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProfitLossService
{
    public function generate(int $companyId, ?int $periodId = null, ?string $fromDate = null, ?string $toDate = null): array
    {
        $query = DB::table('journal_lines as jl')
            ->join('journal_headers as jh', 'jh.id', '=', 'jl.journal_header_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jh.company_id', $companyId)
            ->where('jh.status', 'posted')
            ->where('a.level', 3)
            ->whereIn('a.type', ['revenue', 'expense'])
            ->select(
                'a.id as account_id',
                'a.code',
                'a.name',
                'a.type',
                DB::raw('SUM(jl.debit) as total_debit'),
                DB::raw('SUM(jl.credit) as total_credit'),
            )
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->orderBy('a.code');

        if ($periodId) {
            $query->where('jh.period_id', $periodId);
        }
        if ($fromDate) {
            $query->where('jh.date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('jh.date', '<=', $toDate);
        }

        $rows = $query->get();

        $revenues = [];
        $expenses = [];
        $totalRevenue = 0;
        $totalExpense = 0;

        foreach ($rows as $row) {
            $debit  = (float) $row->total_debit;
            $credit = (float) $row->total_credit;

            if ($row->type === 'revenue') {
                $balance = $credit - $debit; // normal credit
                $revenues[] = ['code' => $row->code, 'name' => $row->name, 'amount' => $balance];
                $totalRevenue += $balance;
            } else {
                $balance = $debit - $credit; // normal debit
                $expenses[] = ['code' => $row->code, 'name' => $row->name, 'amount' => $balance];
                $totalExpense += $balance;
            }
        }

        $netProfit = $totalRevenue - $totalExpense;

        return [
            'revenues'      => $revenues,
            'expenses'      => $expenses,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_profit'    => $netProfit,
        ];
    }
}
