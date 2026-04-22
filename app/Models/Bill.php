<?php

namespace App\Models;

use App\Traits\HasCompanyScope;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    use HasCompanyScope, LogsActivityTrait;

    protected $fillable = [
        'company_id',
        'vendor_id',
        'period_id',
        'bill_no',
        'reference_no',
        'date',
        'due_date',
        'status',
        'currency_code',
        'exchange_rate',
        'subtotal',
        'tax_amount',
        'total',
        'paid_amount',
        'balance_due',
        'notes',
        'journal_header_id',
        'posted_at',
        'approved_by',
        'approved_at',
        'voided_by',
        'voided_at',
        'void_reason',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date'          => 'date',
            'due_date'      => 'date',
            'posted_at'     => 'datetime',
            'approved_at'   => 'datetime',
            'voided_at'     => 'datetime',
            'subtotal'      => 'decimal:2',
            'tax_amount'    => 'decimal:2',
            'total'         => 'decimal:2',
            'paid_amount'   => 'decimal:2',
            'balance_due'   => 'decimal:2',
            'exchange_rate' => 'decimal:6',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(JournalHeader::class, 'journal_header_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BillLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BillPayment::class);
    }

    public function isDraft(): bool    { return $this->status === 'draft'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isPaid(): bool     { return $this->status === 'paid'; }
    public function isVoid(): bool     { return $this->status === 'void'; }
}