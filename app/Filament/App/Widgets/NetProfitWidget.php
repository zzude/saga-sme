<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NetProfitWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $companyId = Auth::user()->company_id;

        $revenue = DB::table('journal_lines as jl')
            ->join('journal_headers as jh', 'jh.id', '=', 'jl.journal_header_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jh.company_id', $companyId)
            ->where('jh.status', 'posted')
            ->where('a.type', 'revenue')
            ->where('a.level', 3)
            ->selectRaw('SUM(jl.credit) - SUM(jl.debit) as total')
            ->value('total') ?? 0;

        $expense = DB::table('journal_lines as jl')
            ->join('journal_headers as jh', 'jh.id', '=', 'jl.journal_header_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jh.company_id', $companyId)
            ->where('jh.status', 'posted')
            ->where('a.type', 'expense')
            ->where('a.level', 3)
            ->selectRaw('SUM(jl.debit) - SUM(jl.credit) as total')
            ->value('total') ?? 0;

        $net = $revenue - $expense;

        return [
            Stat::make('Net Profit', 'MYR ' . number_format($net, 2))
                ->description($net >= 0 ? 'Profitable' : 'Net Loss')
                ->descriptionIcon($net >= 0 ? 'heroicon-m-face-smile' : 'heroicon-m-face-frown')
                ->color($net >= 0 ? 'success' : 'danger'),
        ];
    }
}