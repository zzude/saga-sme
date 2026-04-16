<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\AccountingPeriod;
use App\Models\JournalHeader;
use App\Models\Account;

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

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function accountingPeriods(): HasMany
    {
        return $this->hasMany(AccountingPeriod::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(JournalHeader::class);
    }
}
