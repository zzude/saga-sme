<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user    = Auth::user();
            $company = $user->currentCompany();

            // Redirect ikut state
            if (! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            if (! $company || ! $company->isOnboardingComplete()) {
                $step = $company->onboarding_step ?? 1;
                return redirect()->route('onboarding.step', ['step' => $step]);
            }

            return redirect()->intended('/app');
        }

        return back()->withErrors([
            'email' => 'Email atau kata laluan tidak sah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
