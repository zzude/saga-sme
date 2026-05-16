<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Tender extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'tender_number',
        'purchase_requisition_id',
        'tender_date',
        'title',
        'scope_of_work',
        'ptj_id',
        'objek_sebagai',
        'budget_item_id',
        'project_id',
        'tender_type',
        'advertisement_date',
        'document_sale_start',
        'document_sale_end',
        'site_visit_date',
        'closing_date',
        'opening_date',
        'evaluation_date',
        'award_date',
        'document_price',
        'estimated_amount',
        'awarded_amount',
        'awarded_vendor_id',
        'award_justification',
        'committee_members',
        'status',
        'recommended_by',
        'recommended_at',
        'approved_by',
        'approved_at',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'tender_date'        => 'date',
        'advertisement_date' => 'date',
        'document_sale_start'=> 'date',
        'document_sale_end'  => 'date',
        'site_visit_date'    => 'date',
        'closing_date'       => 'date',
        'opening_date'       => 'date',
        'evaluation_date'    => 'date',
        'award_date'         => 'date',
        'recommended_at'     => 'datetime',
        'approved_at'        => 'datetime',
        'committee_members'  => 'array',
        'document_price'     => 'decimal:2',
        'estimated_amount'   => 'decimal:2',
        'awarded_amount'     => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            if (auth()->check() && auth()->user()->company_id) {
                $query->where('company_id', auth()->user()->company_id);
            }
        });
    }

    public static function generateTenderNumber(string $prefix = 'KPD'): string
    {
        $year = now()->year;
        $last = static::withoutGlobalScope('company')
            ->whereYear('tender_date', $year)
            ->lockForUpdate()
            ->max('tender_number');

        $seq = $last ? (int) substr($last, -3) + 1 : 1;
        return sprintf('%s-T-%s-%03d', $prefix, $year, $seq);
    }

    public function getTenderTypeLabelAttribute(): string
    {
        return match ($this->tender_type) {
            'terbuka'   => 'Tender Terbuka',
            'terhad'    => 'Tender Terhad',
            'rundingan' => 'Rundingan Terus',
            default     => $this->tender_type,
        };
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------
    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function bids(): HasMany
    {
        return $this->hasMany(TenderBid::class);
    }

    public function contract(): HasMany
    {
        return $this->hasMany(ProcurementContract::class);
    }

    public function awardedVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'awarded_vendor_id');
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }
}
