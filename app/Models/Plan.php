<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'max_users', 'max_invoices_per_month',
        'max_customers', 'has_einvoice', 'has_multicurrency',
        'price_monthly', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'has_einvoice'      => 'boolean',
            'has_multicurrency' => 'boolean',
            'is_active'         => 'boolean',
            'price_monthly'     => 'decimal:2',
        ];
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function companyPlans(): HasMany
    {
        return $this->hasMany(CompanyPlan::class);
    }

    public function isUnlimited(string $feature): bool
    {
        return $this->{$feature} === -1;
    }
}
