<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosTransactionItem extends Model
{
    protected $fillable = [
        'transaction_id', 'item_id',
        'description', 'quantity', 'unit_price',
        'discount_percent', 'discount_amount',
        'is_sst_applicable', 'sst_rate', 'sst_amount',
        'subtotal', 'total_amount',
    ];

    protected $casts = [
        'quantity'          => 'decimal:2',
        'unit_price'        => 'decimal:2',
        'discount_percent'  => 'decimal:2',
        'discount_amount'   => 'decimal:2',
        'sst_rate'          => 'decimal:2',
        'sst_amount'        => 'decimal:2',
        'subtotal'          => 'decimal:2',
        'total_amount'      => 'decimal:2',
        'is_sst_applicable' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $item) {
            $gross               = round((float)$item->quantity * (float)$item->unit_price, 2);
            $item->discount_amount = round($gross * ((float)$item->discount_percent / 100), 2);
            $item->subtotal      = round($gross - $item->discount_amount, 2);
            $item->sst_amount    = $item->is_sst_applicable
                ? round($item->subtotal * ((float)$item->sst_rate / 100), 2)
                : 0;
            $item->total_amount  = round($item->subtotal + $item->sst_amount, 2);
        });
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PosTransaction::class, 'transaction_id');
    }
}
