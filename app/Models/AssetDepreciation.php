<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class AssetDepreciation extends Model
{
    protected $fillable = [
        'company_id',
        'asset_id',
        'period_id',
        'depreciation_date',
        'amount',
        'book_value_after',
        'journal_id',
        'notes',
    ];

    protected $casts = [
        'depreciation_date' => 'date',
        'amount'            => 'decimal:2',
        'book_value_after'  => 'decimal:2',
    ];

    // -------------------------------------------------------------------------
    // Global scope — company isolation
    // -------------------------------------------------------------------------
    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            if (auth()->check() && auth()->user()->company_id) {
                $query->where('company_id', auth()->user()->company_id);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------
    public function asset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'asset_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(JournalHeader::class, 'journal_id');
    }
}
