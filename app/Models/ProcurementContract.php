<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ProcurementContract extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'contract_number',
        'tender_id',
        'contract_date',
        'title',
        'scope_of_work',
        'vendor_id',
        'vendor_name',
        'ptj_id',
        'objek_sebagai',
        'project_id',
        'budget_item_id',
        'contract_type',
        'contract_amount',
        'start_date',
        'end_date',
        'performance_bond_required',
        'performance_bond_amount',
        'performance_bond_reference',
        'performance_bond_expiry',
        'total_claimed',
        'total_paid',
        'retention_amount',
        'retention_released',
        'has_variation_order',
        'variation_amount',
        'status',
        'completion_date_actual',
        'signed_by',
        'signed_date',
        'contract_document_path',
        'notes',
    ];

    protected $casts = [
        'contract_date'             => 'date',
        'start_date'                => 'date',
        'end_date'                  => 'date',
        'performance_bond_expiry'   => 'date',
        'completion_date_actual'    => 'date',
        'signed_date'               => 'date',
        'performance_bond_required' => 'boolean',
        'has_variation_order'       => 'boolean',
        'contract_amount'           => 'decimal:2',
        'performance_bond_amount'   => 'decimal:2',
        'total_claimed'             => 'decimal:2',
        'total_paid'                => 'decimal:2',
        'retention_amount'          => 'decimal:2',
        'retention_released'        => 'decimal:2',
        'variation_amount'          => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            if (auth()->check() && auth()->user()->company_id) {
                $query->where('company_id', auth()->user()->company_id);
            }
        });
    }

    public static function generateContractNumber(string $prefix = 'KPD'): string
    {
        $year = now()->year;
        $last = static::withoutGlobalScope('company')
            ->whereYear('contract_date', $year)
            ->lockForUpdate()
            ->max('contract_number');

        $seq = $last ? (int) substr($last, -3) + 1 : 1;
        return sprintf('%s-K-%s-%03d', $prefix, $year, $seq);
    }

    // -------------------------------------------------------------------------
    // Computed attributes
    // -------------------------------------------------------------------------
    public function getRevisedContractAmountAttribute(): float
    {
        return (float) $this->contract_amount + (float) $this->variation_amount;
    }

    public function getBalanceClaimableAttribute(): float
    {
        return $this->revised_contract_amount - (float) $this->total_claimed;
    }

    public function getBalanceRetentionAttribute(): float
    {
        return (float) $this->retention_amount - (float) $this->retention_released;
    }

    public function getCompletionPercentageAttribute(): float
    {
        if ($this->revised_contract_amount <= 0) return 0;
        return round(($this->total_claimed / $this->revised_contract_amount) * 100, 2);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date < now()->toDateString() && $this->status === 'active';
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------
    public function progressClaims(): HasMany
    {
        return $this->hasMany(ProgressClaim::class);
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }
}
