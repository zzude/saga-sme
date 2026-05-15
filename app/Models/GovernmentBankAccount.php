<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class GovernmentBankAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'account_id',
        'gov_account_code',
        'account_name',
        'account_number',
        'bank_name',
        'bank_branch',
        'swift_code',
        'account_type',
        'currency',
        'current_balance',
        'balance_updated_at',
        'is_active',
        'overdraft_limit',
        'notes',
    ];

    protected $casts = [
        'current_balance'    => 'decimal:2',
        'overdraft_limit'    => 'decimal:2',
        'is_active'          => 'boolean',
        'balance_updated_at' => 'datetime',
    ];

    const ACCOUNT_TYPES = [
        'am'         => 'Akaun Am',
        'gaji'       => 'Akaun Gaji',
        'projek'     => 'Akaun Projek',
        'tabung_khas'=> 'Tabung Khas',
        'caruman'    => 'Akaun Caruman',
    ];

    // ── Relationships ─────────────────────────────────────────────
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    // ── Real-time balance dari GL ─────────────────────────────────
    // Computes actual balance from journal_entries where account_id matches
    public function refreshBalanceFromGL(): void
    {
        $dr = DB::table('journal_entries')
            ->where('account_id', $this->account_id)
            ->where('company_id', $this->company_id)
            ->whereHas('journal', fn($q) => $q->where('status', 'posted'))
            ->sum('debit_amount');

        $cr = DB::table('journal_entries')
            ->where('account_id', $this->account_id)
            ->where('company_id', $this->company_id)
            ->sum('credit_amount');

        $this->update([
            'current_balance'    => $dr - $cr,
            'balance_updated_at' => now(),
        ]);
    }

    // ── Available balance (current - overdraft buffer) ────────────
    public function getAvailableBalanceAttribute(): float
    {
        return $this->current_balance + $this->overdraft_limit;
    }

    // ── Scopes ───────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}

