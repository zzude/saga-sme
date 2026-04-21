<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $companyId = Auth::user()->company_id;

        $total = DB::table('journal_lines as jl')
            ->join('journal_headers as jh', 'jh.id', '=', 'jl.journal_header_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jh.company_id', $companyId)
            ->where('jh.status', 'posted')
            ->where('a.type', 'expense')
            ->where('a.level', 3)
            ->selectRaw('SUM(jl.debit) - SUM(jl.credit) as total')
            ->value('total') ?? 0;

        return [
            Stat::make('Total Expenses', 'MYR ' . number_format($total, 2))
                ->description('All posted journals')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
        ];
    }
}