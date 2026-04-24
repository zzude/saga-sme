<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    protected $fillable = [
        'name',
        'registration_number',
        'tax_number',
        'sst_number',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postcode',
        'country',
        'currency',
        'timezone',
        'financial_year_start',
        'logo_path',
        'is_active',
        'owner_id',
        'status',
        'onboarding_step',
        'onboarding_completed_at',
    ];

    protected $casts = [
        'status'                  => CompanyStatus::class,
        'onboarding_completed_at' => 'datetime',
        'financial_year_start'    => 'date',
        'is_active'               => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(CompanyInvitation::class);
    }

    public function taxProfile(): HasOne
    {
        return $this->hasOne(CompanyTaxProfile::class);
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function isOnboardingComplete(): bool
    {
        return ! is_null($this->onboarding_completed_at);
    }

    public function isActive(): bool
    {
        return $this->status === CompanyStatus::Active;
    }

    public function isDraft(): bool
    {
        return $this->status === CompanyStatus::Draft;
    }

    public function isSuspended(): bool
    {
        return $this->status === CompanyStatus::Suspended;
    }
}
