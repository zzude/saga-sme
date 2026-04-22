<?php

namespace App\Filament\App\Widgets;

use App\Models\Bill;
use App\Models\Invoice;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CashFlowWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'treasurer']) ?? false;
    }

    protected function getStats(): array
    {
        $companyId = Auth::user()->company_id;
        $next30 = now()->addDays(30);

        // Expected cash IN — unpaid invoices due within 30 days
        $expectedIn = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['sent', 'partial'])
            ->whereDate('due_date', '<=', $next30)
            ->sum('balance_due');

        // Expected cash OUT — unpaid bills due within 30 days
        $expectedOut = Bill::where('company_id', $companyId)
            ->whereIn('status', ['draft', 'partial'])
            ->whereDate('due_date', '<=', $next30)
            ->sum('balance_due');

        // Overdue receivables
        $overdue = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['sent', 'partial'])
            ->whereDate('due_date', '<', now())
            ->sum('balance_due');

        $netCash = $expectedIn - $expectedOut;

        return [
            Stat::make('Expected Cash In (30 days)', 'MYR ' . number_format($expectedIn, 2))
                ->description('Unpaid invoices due soon')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Expected Cash Out (30 days)', 'MYR ' . number_format($expectedOut, 2))
                ->description('Unpaid bills due soon')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Net Cash Flow', 'MYR ' . number_format($netCash, 2))
                ->description($netCash >= 0 ? 'Positive cash flow' : 'Negative — monitor closely')
                ->descriptionIcon($netCash >= 0 ? 'heroicon-m-face-smile' : 'heroicon-m-exclamation-triangle')
                ->color($netCash >= 0 ? 'success' : 'danger'),

            Stat::make('Overdue Receivables', 'MYR ' . number_format($overdue, 2))
                ->description($overdue > 0 ? 'Follow up required!' : 'All clear')
                ->descriptionIcon($overdue > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->color($overdue > 0 ? 'warning' : 'success'),
        ];
    }
}
