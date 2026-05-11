<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BillplzBill extends Model
{
    protected $fillable = [
        'company_id', 'billplz_id', 'collection_id',
        'billable_type', 'billable_id', 'reference_no',
        'description', 'amount', 'payer_name', 'payer_email',
        'payer_phone', 'status', 'url', 'paid_at', 'paid_amount',
        'transaction_id', 'transaction_status', 'callback_data',
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'decimal:2',
            'paid_at'       => 'datetime',
            'callback_data' => 'array',
        ];
    }

    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
