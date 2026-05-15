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
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->brandName('SAGA SME — Admin')
            // Manual resource registration — do not use discoverResources()
            ->resources([
                // Register admin resources here as the project grows:
                \App\Filament\Resources\Plans\PlanResource::class,
                \App\Filament\Resources\Companies\CompanyResource::class,
                // \App\Filament\Admin\Resources\UserResource::class,
                // \App\Filament\Admin\Resources\CompanyResource::class,
                \App\Filament\Resources\MyInvoisProfiles\MyInvoisProfileResource::class,
                \App\Filament\Resources\AnnualBudgetResource::class,
                \App\Filament\Resources\WarrantAllocationResource::class,
                \App\Filament\Resources\VirementResource::class,
                \App\Filament\Resources\SupplementaryBudgetResource::class,
                \App\Filament\Resources\GovernmentBankAccountResource::class,
                \App\Filament\Resources\AnnualBudgetResource::class,
                \App\Filament\Resources\WarrantAllocationResource::class,
                \App\Filament\Resources\VirementResource::class,
                \App\Filament\Resources\SupplementaryBudgetResource::class,
                \App\Filament\Resources\GovernmentBankAccountResource::class,
            ])
            ->pages([
                \App\Filament\Pages\ActivityLog::class,
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

