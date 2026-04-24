<?php

namespace App\Http\Controllers\Auth;

use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended('/app');
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended('/app');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));

            // Aktifkan company bila email verified
            $company = $request->user()->currentCompany();
            if ($company && $company->isDraft()) {
                $company->update(['status' => CompanyStatus::Active]);
            }
        }

        return redirect()->route('onboarding.step', ['step' => 1])
            ->with('success', 'Email berjaya disahkan! Mari lengkapkan profil syarikat anda.');
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended('/app');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Email pengesahan telah dihantar semula.');
    }
}
