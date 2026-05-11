<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Plan;

class PlanService
{
    public function getPlan(Company $company): Plan
    {
        // Use cached plan_id shortcut, fallback to free
        if ($company->plan_id) {
            return Plan::find($company->plan_id)
                ?? $this->getFreePlan();
        }
        return $this->getFreePlan();
    }

    public function getFreePlan(): Plan
    {
        return Plan::where('slug', 'free')->first();
    }

    public function assignPlan(Company $company, Plan $plan, ?string $notes = null): void
    {
        // Deactivate current
        $company->companyPlans()
            ->where('status', 'active')
            ->update(['status' => 'superseded']);

        // Create new
        $company->companyPlans()->create([
            'plan_id'    => $plan->id,
            'started_at' => now(),
            'expires_at' => null,
            'status'     => 'active',
            'set_by'     => auth()->user()?->name ?? 'system',
            'notes'      => $notes,
        ]);

        // Update shortcut
        $company->update(['plan_id' => $plan->id]);
    }

    // ── Limit Checkers ────────────────────────────────────────────

    public function canAddUser(Company $company): bool
    {
        $plan = $this->getPlan($company);
        if ($plan->isUnlimited('max_users')) return true;
        $count = $company->users()->count();
        return $count < $plan->max_users;
    }

    public function canAddCustomer(Company $company): bool
    {
        $plan = $this->getPlan($company);
        if ($plan->isUnlimited('max_customers')) return true;
        $count = $company->customers()->count();
        return $count < $plan->max_customers;
    }

    public function canCreateInvoice(Company $company): bool
    {
        $plan = $this->getPlan($company);
        if ($plan->isUnlimited('max_invoices_per_month')) return true;
        $count = $company->invoices()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        return $count < $plan->max_invoices_per_month;
    }

    public function canUseEInvoice(Company $company): bool
    {
        return $this->getPlan($company)->has_einvoice;
    }

    public function canUseMultiCurrency(Company $company): bool
    {
        return $this->getPlan($company)->has_multicurrency;
    }

    // ── Usage Summary ─────────────────────────────────────────────

    public function getUsage(Company $company): array
    {
        $plan = $this->getPlan($company);
        return [
            'plan'             => $plan->name,
            'users'            => [
                'used'  => $company->users()->count(),
                'limit' => $plan->max_users === -1 ? '∞' : $plan->max_users,
            ],
            'customers'        => [
                'used'  => $company->customers()->count(),
                'limit' => $plan->max_customers === -1 ? '∞' : $plan->max_customers,
            ],
            'invoices_month'   => [
                'used'  => $company->invoices()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'limit' => $plan->max_invoices_per_month === -1 ? '∞' : $plan->max_invoices_per_month,
            ],
            'has_einvoice'     => $plan->has_einvoice,
            'has_multicurrency'=> $plan->has_multicurrency,
        ];
    }
}
