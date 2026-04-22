<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->login()
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->brandName('SAGA SME')
            // Manual resource registration — do not use discoverResources()
            ->resources([
                \App\Filament\App\Resources\AccountResource::class,
                \App\Filament\App\Resources\AccountingPeriodResource::class,
                \App\Filament\App\Resources\JournalResource::class,
                \App\Filament\Resources\Customers\CustomerResource::class,
                \App\Filament\Resources\Invoices\InvoiceResource::class,
                \App\Filament\Resources\Vendors\VendorResource::class,
                \App\Filament\Resources\Bills\BillResource::class,
                \App\Filament\Resources\BankReconciliations\BankReconciliationResource::class,
                \App\Filament\App\Resources\TaxCodeResource::class,
                \App\Filament\App\Resources\CompanyTaxProfileResource::class,
                ])
            ->pages([
                Dashboard::class,
                    \App\Filament\App\Pages\TrialBalancePage::class,
                    \App\Filament\App\Pages\ProfitLossPage::class,
                    \App\Filament\App\Pages\BalanceSheetPage::class,
                    \App\Filament\App\Pages\ImportCoaPage::class,
                    \App\Filament\App\Pages\ImportJournalPage::class,                    
            ])
            ->widgets([
                AccountWidget::class,
                \App\Filament\App\Widgets\RevenueWidget::class,
                \App\Filament\App\Widgets\ExpenseWidget::class,
                \App\Filament\App\Widgets\NetProfitWidget::class,
                \App\Filament\App\Widgets\ArOutstandingWidget::class,
                \App\Filament\App\Widgets\CashFlowWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling("30s")
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
