<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $company = $user->currentCompany();

        if (! $company) {
            abort(403, 'Tiada syarikat ditemui.');
        }

        if (! $company->isOnboardingComplete()) {
            $step = $company->onboarding_step ?? 1;
            return redirect()->route('onboarding.step', ['step' => $step]);
        }

        return $next($request);
    }
}
