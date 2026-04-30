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
        // FX fields
        'currency_code',
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
            'date'               => 'date',
            'due_date'           => 'date',
            'exchange_rate_date' => 'date',
            'posted_at'          => 'datetime',
            'approved_at'        => 'datetime',
            'voided_at'          => 'datetime',
            'rate_overridden_at' => 'datetime',
            'exchange_rate'      => 'decimal:6',
            'foreign_subtotal'   => 'decimal:2',
            'foreign_tax'        => 'decimal:2',
            'foreign_total'      => 'decimal:2',
            'base_subtotal'      => 'decimal:2',
            'base_tax'           => 'decimal:2',
            'base_total'         => 'decimal:2',
            'subtotal'           => 'decimal:2',
            'tax_amount'         => 'decimal:2',
            'total'              => 'decimal:2',
            'paid_amount'        => 'decimal:2',
            'balance_due'        => 'decimal:2',
        ];
    }
    // ── Relationships ──────────────────────────────────────────
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
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }
    public function rateOverriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rate_overridden_by');
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
    // ── Helpers ───────────────────────────────────────────────
    public function isDraft(): bool    { return $this->status === 'draft'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isPaid(): bool     { return $this->status === 'paid'; }
    public function isVoid(): bool     { return $this->status === 'void'; }
    public function isMYR(): bool      { return $this->currency_code === 'MYR'; }

    public function recalculate(): void
    {
        $this->subtotal    = $this->lines->sum('amount');
        $this->tax_amount  = $this->lines->sum('tax_amount');
        $this->total       = $this->subtotal + $this->tax_amount;
        $this->balance_due = $this->total - $this->paid_amount;

        $this->foreign_subtotal = $this->subtotal;
        $this->foreign_tax      = $this->tax_amount;
        $this->foreign_total    = $this->total;
    }

    public function recalculateBase(): void
    {
        $rate = (float) $this->exchange_rate ?: 1.0;
        $this->base_subtotal = round($this->lines->sum('base_line_total'), 2);
        $this->base_tax      = round((float) $this->tax_amount * $rate, 2);
        $this->base_total    = $this->base_subtotal + $this->base_tax;
    }
}
