<?php

namespace App\Models;

use App\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasCompanyScope;

    protected $fillable = [
        'company_id',
        'customer_code',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postcode',
        'country',
        'registration_no',
        'tax_id',
        'credit_term_days',
        'credit_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit'      => 'decimal:2',
            'credit_term_days'  => 'integer',
            'is_active'         => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getSelectLabelAttribute(): string
    {
        return $this->customer_code . ' — ' . $this->name;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}