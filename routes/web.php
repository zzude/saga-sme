<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\OnboardingController;
use Illuminate\Support\Facades\Route;

// ─── Public ───────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'show'])
        ->name('register');
    Route::post('/register', [RegisterController::class, 'store']);

    Route::get('/login', [LoginController::class, 'show'])
        ->name('login');
    Route::post('/login', [LoginController::class, 'authenticate']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ─── Email Verification ───────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// ─── Onboarding ───────────────────────────────────────────────
// Auth + verified + onboarding belum complete
Route::middleware(['auth', 'verified', 'company.active'])
    ->prefix('onboarding')
    ->name('onboarding.')
    ->group(function () {
        Route::get('/step/{step}', [OnboardingController::class, 'show'])
            ->name('step');
        Route::post('/step/{step}', [OnboardingController::class, 'update']);
    });

// ─── App (Filament handle sendiri) ───────────────────────────
// Middleware stack inject ke Filament panel dalam AppPanelProvider
// Lihat Step 5 nanti

// ─── PDF Routes ───────────────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/invoices/{id}/pdf', function ($id) {
        $invoice = \App\Models\Invoice::with(['customer', 'lines', 'lines.account'])
            ->where('company_id', auth()->user()->company_id)
            ->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.invoice-pdf', compact('invoice'));

        return $pdf->stream('invoice-' . $invoice->invoice_no . '.pdf');
    })->name('invoice.pdf');
});

Route::get('/invoices/{id}/pdf', function ($id) {
    $invoice = \App\Models\Invoice::with(['customer', 'lines', 'lines.account', 'company'])
        ->where('company_id', auth()->user()->company_id)
        ->findOrFail($id);

    $company = $invoice->company;

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.invoice-pdf', compact('invoice', 'company'));
    return $pdf->stream('invoice-' . $invoice->invoice_no . '.pdf');
})->name('invoice.pdf');

// ── Payroll PDF ───────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/payroll/payslip/{run}/{line}', [\App\Http\Controllers\PayrollPdfController::class, 'payslip'])
        ->name('payroll.payslip');
    Route::get('/payroll/payslip-all/{run}', [\App\Http\Controllers\PayrollPdfController::class, 'payslipAll'])
        ->name('payroll.payslip.all');
    Route::get('/payroll/ea-form/{employee}/{year}', [\App\Http\Controllers\PayrollPdfController::class, 'eaForm'])
        ->name('payroll.ea-form');
});

// ── Billplz Payment ───────────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/payment/invoice/{invoice}', [\App\Http\Controllers\PaymentController::class, 'payInvoice'])
        ->name('payment.invoice');
    Route::get('/payment/redirect/{invoice}', [\App\Http\Controllers\PaymentController::class, 'redirect'])
        ->name('billplz.redirect');
});

// Billplz callback — no auth (server-to-server)
Route::post('/billplz/callback', [\App\Http\Controllers\PaymentController::class, 'callback'])
    ->name('billplz.callback');
