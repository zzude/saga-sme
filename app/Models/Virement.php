<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Virement extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'annual_budget_id',
        'virement_no',
        'title',
        'justification',
        'status',
        'total_amount',
        'virement_date',
        'approval_reference',
        'prepared_by',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected $casts = [
        'total_amount'   => 'decimal:2',
        'virement_date'  => 'date',
        'approved_at'    => 'datetime',
    ];

    const STATUS_DRAFT    = 'draft';
    const STATUS_PENDING  = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_POSTED   = 'posted';

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT    => 'Draf',
            self::STATUS_PENDING  => 'Menunggu Kelulusan',
            self::STATUS_APPROVED => 'Diluluskan',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_POSTED   => 'Dipos',
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

    public function virementItems(): HasMany
    {
        return $this->hasMany(VirementItem::class);
    }

    public function fromItems(): HasMany
    {
        return $this->hasMany(VirementItem::class)->where('direction', 'from');
    }

    public function toItems(): HasMany
    {
        return $this->hasMany(VirementItem::class)->where('direction', 'to');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Auto-generate virement_no ─────────────────────────────────
    public static function generateVirementNo(int $companyId, int $year): string
    {
        $count = static::where('company_id', $companyId)
            ->whereHas('annualBudget', fn($q) => $q->where('financial_year', $year))
            ->withTrashed()
            ->count() + 1;

        return sprintf('VIR%d/%03d', $year, $count);
    }

    // ── Post virement: adjust budget_items ───────────────────────
    public function post(): bool
    {
        if ($this->status !== self::STATUS_APPROVED) return false;

        DB::transaction(function () {
            // Deduct FROM items
            foreach ($this->fromItems as $item) {
                $bi = $item->budgetItem;
                $bi->decrement('revised_amount', $item->amount);
                $bi->decrement('balance_amount', $item->amount);
            }

            // Add TO items
            foreach ($this->toItems as $item) {
                $bi = $item->budgetItem;
                $bi->increment('revised_amount', $item->amount);
                $bi->increment('balance_amount', $item->amount);
            }

            $this->update(['status' => self::STATUS_POSTED]);
        });

        return true;
    }

    // ── Validate FROM >= amount ───────────────────────────────────
    public function validateBalance(): array
    {
        $errors = [];
        foreach ($this->fromItems as $item) {
            if (!$item->budgetItem->hasAvailableBalance($item->amount)) {
                $errors[] = "Baki tidak mencukupi untuk: {$item->budgetItem->description}";
            }
        }
        return $errors;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }
}
