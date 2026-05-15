<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class WarrantAllocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'annual_budget_id',
        'warrant_no',
        'warrant_type',
        'title',
        'description',
        'status',
        'total_amount',
        'used_amount',
        'balance_amount',
        'issue_date',
        'expiry_date',
        'reference_doc',
        'issued_by',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected $casts = [
        'total_amount'   => 'decimal:2',
        'used_amount'    => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'issue_date'     => 'date',
        'expiry_date'    => 'date',
        'approved_at'    => 'datetime',
    ];

    const STATUS_DRAFT     = 'draft';
    const STATUS_ISSUED    = 'issued';
    const STATUS_ACTIVE    = 'active';
    const STATUS_EXHAUSTED = 'exhausted';
    const STATUS_CANCELLED = 'cancelled';

    const TYPE_ASAL      = 'peruntukan_asal';
    const TYPE_TAMBAHAN  = 'peruntukan_tambahan';
    const TYPE_PINDAHAN  = 'pindahan';

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT     => 'Draf',
            self::STATUS_ISSUED    => 'Dikeluarkan',
            self::STATUS_ACTIVE    => 'Aktif',
            self::STATUS_EXHAUSTED => 'Habis Digunakan',
            self::STATUS_CANCELLED => 'Dibatalkan',
        ];
    }

    public static function types(): array
    {
        return [
            self::TYPE_ASAL     => 'Peruntukan Asal',
            self::TYPE_TAMBAHAN => 'Peruntukan Tambahan',
            self::TYPE_PINDAHAN => 'Pindahan Waran',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function annualBudget(): BelongsTo
    {
        return $this->belongsTo(AnnualBudget::class);
    }

    public function warrantItems(): HasMany
    {
        return $this->hasMany(WarrantItem::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Scopes ───────────────────────────────────────────────────
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForBudget(Builder $query, int $budgetId): Builder
    {
        return $query->where('annual_budget_id', $budgetId);
    }

    // ── Auto-generate warrant_no ──────────────────────────────────
    public static function generateWarrantNo(int $companyId, int $year): string
    {
        $count = static::where('company_id', $companyId)
            ->whereHas('annualBudget', fn($q) => $q->where('financial_year', $year))
            ->withTrashed()
            ->count() + 1;

        return sprintf('W%d/%03d', $year, $count);
    }

    // ── Consume from warrant ──────────────────────────────────────
    public function consume(float $amount): bool
    {
        if ($this->balance_amount < $amount) return false;

        $this->increment('used_amount', $amount);
        $this->decrement('balance_amount', $amount);

        if ($this->balance_amount <= 0) {
            $this->update(['status' => self::STATUS_EXHAUSTED]);
        }

        return true;
    }

    public function isUsable(): bool
    {
        return in_array($this->status, [self::STATUS_ISSUED, self::STATUS_ACTIVE])
            && $this->balance_amount > 0;
    }
}
