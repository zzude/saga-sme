<?php

namespace App\Http\Middleware;

use App\Enums\CompanyStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveCompany
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

        return match($company->status) {
            CompanyStatus::Active    => $next($request),
            CompanyStatus::Draft     => redirect()->route('verification.notice')
                                            ->with('warning', 'Sila sahkan email anda terlebih dahulu.'),
            CompanyStatus::Suspended => abort(403, 'Akaun syarikat anda telah digantung. Sila hubungi support.'),
        };
    }
}
