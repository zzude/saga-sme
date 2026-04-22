<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;

class ArOutstandingWidget extends BaseWidget
{
    public static function canView(): bool {
        return auth()->user()?->hasAnyRole(["super_admin", "admin", "treasurer"]) ?? false;
    }

    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $companyId = Auth::user()->company_id;

        $outstanding = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['sent', 'partial'])
            ->sum('balance_due');

        $count = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['sent', 'partial'])
            ->count();

        return [
            Stat::make('AR Outstanding', 'MYR ' . number_format($outstanding, 2))
                ->description($count . ' unpaid invoice(s)')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),
        ];
    }
}