<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RevenueWidget extends BaseWidget
{
    public static function canView(): bool {
        return auth()->user()?->hasAnyRole(["super_admin", "admin", "treasurer"]) ?? false;
    }

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $companyId = Auth::user()->company_id;

        $total = DB::table('journal_lines as jl')
            ->join('journal_headers as jh', 'jh.id', '=', 'jl.journal_header_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jh.company_id', $companyId)
            ->where('jh.status', 'posted')
            ->where('a.type', 'revenue')
            ->where('a.level', 3)
            ->selectRaw('SUM(jl.credit) - SUM(jl.debit) as total')
            ->value('total') ?? 0;

        return [
            Stat::make('Total Revenue', 'MYR ' . number_format($total, 2))
                ->description('All posted journals')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
        ];
    }
}