<?php

namespace App\Filament\App\Widgets;

use App\Services\PlanService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PlanUsageWidget extends BaseWidget
{
    protected static ?int $sort = 10;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    protected function getStats(): array
    {
        $company  = Auth::user()->currentCompany();
        $usage    = app(PlanService::class)->getUsage($company);

        $invoiceUsed  = $usage['invoices_month']['used'];
        $invoiceLimit = $usage['invoices_month']['limit'];
        $invoicePct   = $invoiceLimit === '∞' ? 0 : ($invoiceLimit > 0 ? round($invoiceUsed / $invoiceLimit * 100) : 100);

        $customerUsed  = $usage['customers']['used'];
        $customerLimit = $usage['customers']['limit'];
        $customerPct   = $customerLimit === '∞' ? 0 : ($customerLimit > 0 ? round($customerUsed / $customerLimit * 100) : 100);

        $userUsed  = $usage['users']['used'];
        $userLimit = $usage['users']['limit'];
        $userPct   = $userLimit === '∞' ? 0 : ($userLimit > 0 ? round($userUsed / $userLimit * 100) : 100);

        return [
            Stat::make('Plan Semasa', $usage['plan'])
                ->description(
                    ($usage['has_einvoice'] ? '✓ e-Invoice  ' : '✗ e-Invoice  ') .
                    ($usage['has_multicurrency'] ? '✓ Multi-FX' : '✗ Multi-FX')
                )
                ->color('info')
                ->icon('heroicon-o-credit-card'),

            Stat::make('Invois Bulan Ini', $invoiceUsed . ' / ' . $invoiceLimit)
                ->description($invoiceLimit === '∞' ? 'Unlimited' : $invoicePct . '% digunakan')
                ->color($invoicePct >= 90 ? 'danger' : ($invoicePct >= 70 ? 'warning' : 'success'))
                ->icon('heroicon-o-document-text'),

            Stat::make('Pelanggan', $customerUsed . ' / ' . $customerLimit)
                ->description($customerLimit === '∞' ? 'Unlimited' : $customerPct . '% digunakan')
                ->color($customerPct >= 90 ? 'danger' : ($customerPct >= 70 ? 'warning' : 'success'))
                ->icon('heroicon-o-users'),

            Stat::make('Pengguna', $userUsed . ' / ' . $userLimit)
                ->description($userLimit === '∞' ? 'Unlimited' : $userPct . '% digunakan')
                ->color($userPct >= 90 ? 'danger' : ($userPct >= 70 ? 'warning' : 'success'))
                ->icon('heroicon-o-user-group'),
        ];
    }
}
