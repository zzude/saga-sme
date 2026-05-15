<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Account;

class BudgetItem extends Model
{
    protected $fillable = [
        'annual_budget_id',
        'company_id',
        'account_id',
        'object_class',
        'object_code',
        'description',
        'original_amount',
        'revised_amount',
        'allocated_amount',
        'actual_amount',
        'balance_amount',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'original_amount'  => 'decimal:2',
        'revised_amount'   => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'actual_amount'    => 'decimal:2',
        'balance_amount'   => 'decimal:2',
        'sort_order'       => 'integer',
    ];

    // Kategori objek kerajaan Malaysia
    const OBJECT_CLASSES = [
        'emolumen'     => 'Emolumen (10000)',
        'perkhidmatan' => 'Perkhidmatan & Bekalan (20000)',
        'aset'         => 'Perolehan Aset (30000)',
        'pinjaman'     => 'Bayaran Pinjaman (40000)',
        'subsidi'      => 'Subsidi & Bantuan Sosial (50000)',
        'lain'         => 'Lain-lain Perbelanjaan',
    ];

    // ── Relationships ─────────────────────────────────────────────
    public function annualBudget(): BelongsTo
    {
        return $this->belongsTo(AnnualBudget::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warrantItems(): HasMany
    {
        return $this->hasMany(WarrantItem::class);
    }

    public function virementItems(): HasMany
    {
        return $this->hasMany(VirementItem::class);
    }

    public function supplementaryBudgets(): HasMany
    {
        return $this->hasMany(SupplementaryBudget::class);
    }

    // ── Helpers ───────────────────────────────────────────────────
    public function recalculateBalance(): void
    {
        $this->update([
            'balance_amount' => $this->revised_amount - $this->allocated_amount,
        ]);
    }

    public function getUtilisationPercentAttribute(): float
    {
        if ($this->revised_amount <= 0) return 0;
        return round(($this->actual_amount / $this->revised_amount) * 100, 2);
    }

    public function hasAvailableBalance(float $amount): bool
    {
        return $this->balance_amount >= $amount;
    }
}


