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
        // FX fields
        'currency_code',
        'currency_id',
        'exchange_rate',
        'exchange_rate_date',
        'rate_source',
        'override_reason',
        'rate_overridden_by',
        'rate_overridden_at',
        // Foreign currency totals
        'foreign_subtotal',
        'foreign_tax',
        'foreign_total',
        // MYR base totals (GL truth)
        'base_subtotal',
        'base_tax',
        'base_total',
        // Document totals
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
            'date'                => 'date',
            'due_date'            => 'date',
            'exchange_rate_date'  => 'date',
            'posted_at'           => 'datetime',
            'rate_overridden_at'  => 'datetime',
            'einvoice_submitted_at'  => 'datetime',
            'einvoice_validated_at'  => 'datetime',
            'exchange_rate'       => 'decimal:6',
            'foreign_subtotal'    => 'decimal:2',
            'foreign_tax'         => 'decimal:2',
            'foreign_total'       => 'decimal:2',
            'base_subtotal'       => 'decimal:2',
            'base_tax'            => 'decimal:2',
            'base_total'          => 'decimal:2',
            'subtotal'            => 'decimal:2',
            'tax_amount'          => 'decimal:2',
            'total'               => 'decimal:2',
            'paid_amount'         => 'decimal:2',
            'balance_due'         => 'decimal:2',
        ];
    }
    // ── Relationships ──────────────────────────────────────────
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id', 'code');
    }
    public function rateOverriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rate_overridden_by');
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
    // ── Helpers ───────────────────────────────────────────────
    public function isDraft(): bool   { return $this->status === 'draft'; }
    public function isSent(): bool    { return $this->status === 'sent'; }
    public function isPaid(): bool    { return $this->status === 'paid'; }
    public function isVoid(): bool    { return $this->status === 'void'; }
    public function isPartial(): bool { return $this->status === 'partial'; }
    public function isPosted(): bool  { return $this->status === 'posted'; }
    public function isMYR(): bool     { return $this->currency_code === 'MYR'; }

    public function recalculate(): void
    {
        $this->subtotal   = $this->lines->sum('amount');
        $this->tax_amount = $this->lines->sum('tax_amount');
        $this->total      = $this->subtotal + $this->tax_amount;
        $this->balance_due = $this->total - $this->paid_amount;

        // Foreign currency totals (same as doc totals for MYR invoices)
        $this->foreign_subtotal = $this->subtotal;
        $this->foreign_tax      = $this->tax_amount;
        $this->foreign_total    = $this->total;
    }

    /** Compute MYR base totals from lines — call before GL posting */
    public function recalculateBase(): void
    {
        $rate = (float) $this->exchange_rate ?: 1.0;
        $this->base_subtotal = round($this->lines->sum('base_line_total'), 2);
        $this->base_tax      = round((float) $this->tax_amount * $rate, 2);
        $this->base_total    = $this->base_subtotal + $this->base_tax;
    }
}
