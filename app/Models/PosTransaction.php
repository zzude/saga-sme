<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class PosTransaction extends Model
{
    protected $fillable = [
        'company_id', 'session_id', 'transaction_no',
        'subtotal', 'discount_amount', 'tax_amount', 'total_amount',
        'payment_method', 'amount_tendered', 'change_amount',
        'customer_name', 'notes',
        'status', 'voided_by', 'voided_at', 'void_reason',
        'journal_id', 'created_by',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'amount_tendered' => 'decimal:2',
        'change_amount'   => 'decimal:2',
        'voided_at'       => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            if (auth()->check() && auth()->user()->company_id) {
                $query->where('company_id', auth()->user()->company_id);
            }
        });
    }

    public static function generateTransactionNo(): string
    {
        $year = now()->year;
        $last = static::withoutGlobalScope('company')
            ->whereYear('created_at', $year)
            ->max('transaction_no');
        $seq = $last ? (int) substr($last, -5) + 1 : 1;
        return sprintf('POS-%s-%05d', $year, $seq);
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash'   => 'Tunai',
            'card'   => 'Kad',
            'qr'     => 'DuitNow QR',
            'credit' => 'Kredit',
            'mixed'  => 'Pelbagai',
            default  => $this->payment_method,
        };
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosTransactionItem::class, 'transaction_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'session_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(JournalHeader::class, 'journal_id');
    }
}
