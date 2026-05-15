<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplementaryBudget extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'annual_budget_id',
        'supplementary_no',
        'title',
        'justification',
        'status',
        'amount',
        'budget_item_id',
        'funding_source',
        'effective_date',
        'supporting_doc',
        'prepared_by',
        'approved_by',
        'approved_at',
        'approval_notes',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'effective_date' => 'date',
        'approved_at'    => 'datetime',
    ];

    const STATUS_DRAFT     = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED  = 'approved';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_POSTED    = 'posted';

    const FUNDING_SOURCES = [
        'peruntukan_kerajaan' => 'Peruntukan Kerajaan',
        'tabung_khas'         => 'Tabung Khas',
        'caruman_agensi'      => 'Caruman Agensi',
        'geran_luar'          => 'Geran Luar',
        'lain'                => 'Lain-lain',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT     => 'Draf',
            self::STATUS_SUBMITTED => 'Dikemukakan',
            self::STATUS_APPROVED  => 'Diluluskan',
            self::STATUS_REJECTED  => 'Ditolak',
            self::STATUS_POSTED    => 'Dipos',
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

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Auto-generate supplementary_no ───────────────────────────
    public static function generateNo(int $companyId, int $year): string
    {
        $count = static::where('company_id', $companyId)
            ->whereHas('annualBudget', fn($q) => $q->where('financial_year', $year))
            ->withTrashed()
            ->count() + 1;

        return sprintf('AT%d/%03d', $year, $count);
    }

    // ── Post: adjust budget_item ──────────────────────────────────
    public function post(): bool
    {
        if ($this->status !== self::STATUS_APPROVED) return false;

        $bi = $this->budgetItem;
        $bi->increment('original_amount', $this->amount);
        $bi->increment('revised_amount', $this->amount);
        $bi->increment('balance_amount', $this->amount);

        // Update parent budget total
        $this->annualBudget->recalculateTotals();

        $this->update(['status' => self::STATUS_POSTED]);

        return true;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED]);
    }
}
