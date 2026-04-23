<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

use App\Services\EInvoice\AuthService;
use App\Services\EInvoice\PayloadBuilder;
use App\Services\EInvoice\SubmissionService;
use App\Services\EInvoice\StatusService;
use App\Services\EInvoice\MyInvoisService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MyInvoisService::class, function ($app) {
            $auth       = new AuthService();
            $builder    = new PayloadBuilder();
            $submission = new SubmissionService($auth, $builder);
            $status     = new StatusService($auth);

            return new MyInvoisService($auth, $builder, $submission, $status);
        });
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
