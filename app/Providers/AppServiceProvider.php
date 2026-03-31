<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // super_admin bypasses all policy checks
        Gate::before(function ($user, string $ability) {
            if ($user->hasRole('super_admin')) {
                return true;
            }
        });
    }
}
