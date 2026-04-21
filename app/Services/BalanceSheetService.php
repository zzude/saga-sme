<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class BalanceSheetService
{
    public function generate(int $companyId, ?string $asOfDate = null, ?int $periodId = null): array
    {
        $query = DB::table('journal_lines as jl')
            ->join('journal_headers as jh', 'jh.id', '=', 'jl.journal_header_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jh.company_id', $companyId)
            ->where('jh.status', 'posted')
            ->where('a.level', 3)
            ->whereIn('a.type', ['asset', 'liability', 'equity'])
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

        if ($asOfDate) {
            $query->where('jh.date', '<=', $asOfDate);
        }
        if ($periodId) {
            $query->where('jh.period_id', $periodId);
        }

        $rows = $query->get();

        $assets      = [];
        $liabilities = [];
        $equity      = [];
        $totalAsset  = 0;
        $totalLiab   = 0;
        $totalEquity = 0;

        foreach ($rows as $row) {
            $debit  = (float) $row->total_debit;
            $credit = (float) $row->total_credit;

            if ($row->type === 'asset') {
                $balance = $debit - $credit;
                $assets[] = ['code' => $row->code, 'name' => $row->name, 'amount' => $balance];
                $totalAsset += $balance;
            } elseif ($row->type === 'liability') {
                $balance = $credit - $debit;
                $liabilities[] = ['code' => $row->code, 'name' => $row->name, 'amount' => $balance];
                $totalLiab += $balance;
            } else {
                $balance = $credit - $debit;
                $equity[] = ['code' => $row->code, 'name' => $row->name, 'amount' => $balance];
                $totalEquity += $balance;
            }
        }

        // Include current period net profit into equity
        $plService = new ProfitLossService();
        $pl = $plService->generate($companyId, $periodId, null, $asOfDate);
        $netProfit = $pl['net_profit'];
        $totalEquity += $netProfit;

        $totalLiabEquity = $totalLiab + $totalEquity;
        $isBalanced = abs($totalAsset - $totalLiabEquity) < 0.01;

        return [
            'assets'            => $assets,
            'liabilities'       => $liabilities,
            'equity'            => $equity,
            'net_profit'        => $netProfit,
            'total_asset'       => $totalAsset,
            'total_liability'   => $totalLiab,
            'total_equity'      => $totalEquity,
            'total_liab_equity' => $totalLiabEquity,
            'is_balanced'       => $isBalanced,
        ];
    }
}
