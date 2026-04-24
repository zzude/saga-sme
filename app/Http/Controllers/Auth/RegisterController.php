<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, RegistrationService $service): RedirectResponse
    {
        $service->register($request->validated());

        return redirect()->route('verification.notice')
            ->with('success', 'Pendaftaran berjaya! Sila semak email anda.');
    }
}
