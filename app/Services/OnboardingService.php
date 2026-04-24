<?php

namespace App\Services;

use App\Models\Company;

class OnboardingService
{
    public function processStep(int $step, Company $company, array $data): void
    {
        match($step) {
            1 => $this->processStepOne($company, $data),
            2 => $this->processStepTwo($company, $data),
            3 => $this->processStepThree($company, $data),
        };
    }

    // Step 1 — Company Profile
    private function processStepOne(Company $company, array $data): void
    {
        $company->update([
            'registration_number' => $data['registration_number'] ?? null,
            'phone'               => $data['phone'] ?? null,
            'address'             => $data['address'] ?? null,
            'city'                => $data['city'] ?? null,
            'state'               => $data['state'] ?? null,
            'postcode'            => $data['postcode'] ?? null,
            'financial_year_start'=> $data['financial_year_start'] ?? null,
        ]);
    }

    // Step 2 — Tax Info
    private function processStepTwo(Company $company, array $data): void
    {
        $company->update([
            'tax_number' => $data['tax_number'] ?? null,
            'sst_number' => $data['sst_number'] ?? null,
        ]);
    }

    // Step 3 — Chart of Accounts seed
    private function processStepThree(Company $company, array $data): void
    {
        $tier = $data['coa_tier'] ?? 'standard';

        app(CoaSeederService::class)->seedForCompany($company, $tier);
    }
}
