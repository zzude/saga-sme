<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $fillable = [
        'company_id',
        'quotation_id',
        'line_no',
        'description',
        'detail',
        'unit_of_measure',
        'quantity',
        'unit_price',
        'gross_amount',
        'discount_percent',
        'discount_amount',
        'net_amount',
        'is_sst_applicable',
        'sst_rate',
        'sst_amount',
        'total_amount',
        'item_id',
    ];

    protected $casts = [
        'quantity'          => 'decimal:2',
        'unit_price'        => 'decimal:2',
        'gross_amount'      => 'decimal:2',
        'discount_percent'  => 'decimal:2',
        'discount_amount'   => 'decimal:2',
        'net_amount'        => 'decimal:2',
        'is_sst_applicable' => 'boolean',
        'sst_rate'          => 'decimal:2',
        'sst_amount'        => 'decimal:2',
        'total_amount'      => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Auto-set company_id from parent quotation
        static::creating(function (self $item) {
            if (empty($item->company_id) && $item->quotation_id) {
                $item->company_id = Quotation::find($item->quotation_id)?->company_id
                    ?? auth()->user()->company_id;
            }
        });

        static::saving(function (self $item) {
            $item->gross_amount    = round((float)$item->quantity * (float)$item->unit_price, 2);
            $item->discount_amount = round($item->gross_amount * ((float)$item->discount_percent / 100), 2);
            $item->net_amount      = round($item->gross_amount - $item->discount_amount, 2);
            $item->sst_amount      = $item->is_sst_applicable
                ? round($item->net_amount * ((float)$item->sst_rate / 100), 2)
                : 0;
            $item->total_amount    = round($item->net_amount + $item->sst_amount, 2);
        });
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
