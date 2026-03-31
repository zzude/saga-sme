<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    protected function casts(): array
    {
        return [
            'financial_year_start' => 'date',
            'is_active'            => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
