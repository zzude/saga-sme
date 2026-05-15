<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class AnnualBudget extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'budget_no',
        'financial_year',
        'title',
        'description',
        'status',
        'total_amount',
        'allocated_amount',
        'balance_amount',
        'effective_date',
        'expiry_date',
        'prepared_by',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected $casts = [
        'financial_year'   => 'integer',
        'total_amount'     => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'balance_amount'   => 'decimal:2',
        'effective_date'   => 'date',
        'expiry_date'      => 'date',
        'approved_at'      => 'datetime',
    ];

    // ── Statuses ──────────────────────────────────────────────────
    const STATUS_DRAFT     = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED  = 'approved';
    const STATUS_ACTIVE    = 'active';
    const STATUS_CLOSED    = 'closed';

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT     => 'Draf',
            self::STATUS_SUBMITTED => 'Dikemukakan',
            self::STATUS_APPROVED  => 'Diluluskan',
            self::STATUS_ACTIVE    => 'Aktif',
            self::STATUS_CLOSED    => 'Ditutup',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function budgetItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    public function warrantAllocations(): HasMany
    {
        return $this->hasMany(WarrantAllocation::class);
    }

    public function virements(): HasMany
    {
        return $this->hasMany(Virement::class);
    }

    public function supplementaryBudgets(): HasMany
    {
        return $this->hasMany(SupplementaryBudget::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Scopes ───────────────────────────────────────────────────
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('financial_year', $year);
    }

    // ── Auto-generate budget_no ───────────────────────────────────
    public static function generateBudgetNo(int $companyId, int $year): string
    {
        $count = static::where('company_id', $companyId)
            ->where('financial_year', $year)
            ->withTrashed()
            ->count() + 1;

        return sprintf('BP%d/%03d', $year, $count);
    }

    // ── Recalculate totals from items ─────────────────────────────
    public function recalculateTotals(): void
    {
        $total     = $this->budgetItems()->sum('original_amount');
        $allocated = $this->budgetItems()->sum('allocated_amount');

        $this->update([
            'total_amount'     => $total,
            'allocated_amount' => $allocated,
            'balance_amount'   => $total - $allocated,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────
    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED]);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
