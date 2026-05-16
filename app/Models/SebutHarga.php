<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class SebutHarga extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'sh_number',
        'purchase_requisition_id',
        'sh_date',
        'title',
        'scope_of_work',
        'ptj_id',
        'objek_sebagai',
        'budget_item_id',
        'min_quotations',
        'closing_date',
        'evaluation_date',
        'estimated_amount',
        'awarded_amount',
        'awarded_vendor_id',
        'awarded_date',
        'award_justification',
        'status',
        'approved_by',
        'approved_at',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'sh_date'         => 'date',
        'closing_date'    => 'date',
        'evaluation_date' => 'date',
        'awarded_date'    => 'date',
        'approved_at'     => 'datetime',
        'estimated_amount' => 'decimal:2',
        'awarded_amount'   => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            if (auth()->check() && auth()->user()->company_id) {
                $query->where('company_id', auth()->user()->company_id);
            }
        });
    }

    public static function generateShNumber(): string
    {
        $year = now()->year;
        $last = static::withoutGlobalScope('company')
            ->whereYear('sh_date', $year)
            ->lockForUpdate()
            ->max('sh_number');

        $seq = $last ? (int) substr($last, -5) + 1 : 1;
        return sprintf('SH-%s-%05d', $year, $seq);
    }

    public function getQuotationCountAttribute(): int
    {
        return $this->quotations()->count();
    }

    public function getMeetsMinQuotationsAttribute(): bool
    {
        return $this->quotation_count >= $this->min_quotations;
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------
    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(ShQuotation::class);
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function awardedVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'awarded_vendor_id');
    }

    public function localOrder(): HasMany
    {
        return $this->hasMany(LocalOrder::class, 'sebut_harga_id');
    }
}
