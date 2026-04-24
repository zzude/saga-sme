<?php

namespace App\Http\Controllers;

use App\Services\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    private const TOTAL_STEPS = 3;

    public function show(int $step, Request $request): View|RedirectResponse
    {
        $company     = $request->user()->currentCompany();
        $currentStep = $company->onboarding_step ?? 1;

        // Tak boleh skip ke hadapan
        if ($step > $currentStep) {
            return redirect()->route('onboarding.step', ['step' => $currentStep]);
        }

        // Clamp step dalam range 1-3
        $step = max(1, min($step, self::TOTAL_STEPS));

        return view("onboarding.steps.{$step}", [
            'company'     => $company,
            'currentStep' => $currentStep,
            'totalSteps'  => self::TOTAL_STEPS,
        ]);
    }

    public function update(int $step, Request $request, OnboardingService $service): RedirectResponse
    {
        $company  = $request->user()->currentCompany();
        $nextStep = $step + 1;

        $service->processStep($step, $company, $request->all());

        if ($nextStep > self::TOTAL_STEPS) {
            $company->update([
                'onboarding_completed_at' => now(),
                'onboarding_step'         => self::TOTAL_STEPS,
            ]);

            return redirect('/app')
                ->with('success', 'Selamat datang ke SAGA SME! ��');
        }

        $company->update(['onboarding_step' => $nextStep]);

        return redirect()->route('onboarding.step', ['step' => $nextStep]);
    }
}
