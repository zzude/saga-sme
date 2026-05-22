<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class StockMovement extends Model
{
    protected $fillable = [
        'company_id',
        'item_id',
        'type',
        'quantity',
        'unit_cost',
        'balance_after',
        'reference_type',
        'reference_id',
        'reference_no',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity'     => 'decimal:2',
        'unit_cost'    => 'decimal:2',
        'balance_after'=> 'decimal:2',
    ];

    // -------------------------------------------------------------------------
    // Global scope
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
    // Helpers
    // -------------------------------------------------------------------------
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'in'         => 'Masuk',
            'out'        => 'Keluar',
            'adjustment' => 'Pelarasan',
            'opening'    => 'Baki Pembuka',
            default      => $this->type,
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'in'         => 'success',
            'out'        => 'danger',
            'adjustment' => 'warning',
            'opening'    => 'info',
            default      => 'gray',
        };
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }
}
