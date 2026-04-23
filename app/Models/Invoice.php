<?php

namespace App\Models;

use App\Traits\HasCompanyScope;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasCompanyScope, LogsActivityTrait;

    protected $fillable = [
        'company_id',
        'customer_id',
        'period_id',
        'invoice_no',
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
        'posted_at',
        'created_by',
        'updated_by',
        // e-Invoice
        'einvoice_status',
        'einvoice_uuid',
        'einvoice_submission_uid',
        'einvoice_long_id',
        'einvoice_errors',
        'einvoice_submitted_at',
        'einvoice_validated_at',
    ];

    protected function casts(): array
    {
        return [
            'date'          => 'date',
            'due_date'      => 'date',
            'posted_at'     => 'datetime',
            'subtotal'      => 'decimal:2',
            'tax_amount'    => 'decimal:2',
            'total'         => 'decimal:2',
            'paid_amount'   => 'decimal:2',
            'balance_due'   => 'decimal:2',
            'exchange_rate' => 'decimal:6',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(InvoiceStatusLog::class);
    }

    public function isDraft(): bool   { return $this->status === 'draft'; }
    public function isSent(): bool    { return $this->status === 'sent'; }
    public function isPaid(): bool    { return $this->status === 'paid'; }
    public function isVoid(): bool    { return $this->status === 'void'; }
    public function isPartial(): bool { return $this->status === 'partial'; }

    public function recalculate(): void
    {
        $this->subtotal   = $this->lines->sum('amount');
        $this->tax_amount = $this->lines->sum('tax_amount');
        $this->total      = $this->subtotal + $this->tax_amount;
        $this->balance_due = $this->total - $this->paid_amount;
    }
}