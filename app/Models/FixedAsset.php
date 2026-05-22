<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class FixedAsset extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'company_id',
        'asset_no',
        'name',
        'description',
        'category_id',
        // Acquisition
        'purchase_date',
        'purchase_amount',
        'salvage_value',
        'useful_life_years',
        'depreciation_method',
        'vendor_id',
        'vendor_invoice_no',
        'purchase_journal_id',
        // Location
        'location',
        'assigned_to',
        // Current value
        'current_book_value',
        'accumulated_depreciation',
        // Status
        'status',
        'disposed_at',
        'disposal_proceeds',
        'disposal_journal_id',
    ];

    protected $casts = [
        'purchase_date'            => 'date',
        'disposed_at'              => 'date',
        'purchase_amount'          => 'decimal:2',
        'salvage_value'            => 'decimal:2',
        'current_book_value'       => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'disposal_proceeds'        => 'decimal:2',
        'useful_life_years'        => 'integer',
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
    // Media Library — collections
    // -------------------------------------------------------------------------
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->acceptsMimeTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
                'image/webp',
            ]);
    }

    // -------------------------------------------------------------------------
    // Asset number generation
    // -------------------------------------------------------------------------
    public static function generateAssetNo(): string
    {
        $year = now()->year;
        $last = static::withoutGlobalScope('company')
            ->whereYear('created_at', $year)
            ->max('asset_no');

        $seq = $last ? (int) substr($last, -5) + 1 : 1;
        return sprintf('FA-%s-%05d', $year, $seq);
    }

    // -------------------------------------------------------------------------
    // Depreciation calculation helpers
    // -------------------------------------------------------------------------
    public function monthlyDepreciation(): float
    {
        if ($this->depreciation_method === 'straight_line') {
            $annual = ($this->purchase_amount - $this->salvage_value) / $this->useful_life_years;
            return round($annual / 12, 2);
        }

        // Reducing balance — annual rate = 1 - (salvage/cost)^(1/n)
        $rate   = 1 - pow(
            ($this->salvage_value > 0 ? $this->salvage_value : 1) / $this->purchase_amount,
            1 / $this->useful_life_years
        );
        $annual = $this->current_book_value * $rate;
        return round($annual / 12, 2);
    }

    public function isFullyDepreciated(): bool
    {
        return round($this->current_book_value, 2) <= round($this->salvage_value, 2);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active'      => 'Aktif',
            'disposed'    => 'Dilupuskan',
            'written_off' => 'Dihapus Kira',
            default       => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active'      => 'success',
            'disposed'    => 'warning',
            'written_off' => 'danger',
            default       => 'gray',
        };
    }

    public function getDepreciationMethodLabelAttribute(): string
    {
        return match ($this->depreciation_method) {
            'straight_line'     => 'Garis Lurus',
            'reducing_balance'  => 'Baki Berkurangan',
            default             => $this->depreciation_method,
        };
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------
    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function depreciations(): HasMany
    {
        return $this->hasMany(AssetDepreciation::class, 'asset_id')->orderBy('depreciation_date');
    }

    public function purchaseJournal(): BelongsTo
    {
        return $this->belongsTo(JournalHeader::class, 'purchase_journal_id');
    }

    public function disposalJournal(): BelongsTo
    {
        return $this->belongsTo(JournalHeader::class, 'disposal_journal_id');
    }
}
