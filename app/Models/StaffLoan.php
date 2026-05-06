<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffLoan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'loan_no',
        'employee_id',
        'loan_type',
        'amount_applied',
        'amount_approved',
        'interest_rate',
        'tenure_months',
        'status',
        'applied_date',
        'approved_date',
        'disbursed_date',
        'account_id',
        'disbursement_account_id',
        'journal_id',
        'approved_by',
        'notes',
    ];

    protected $casts = [
        'amount_applied'    => 'decimal:2',
        'amount_approved'   => 'decimal:2',
        'interest_rate'     => 'decimal:2',
        'tenure_months'     => 'integer',
        'applied_date'      => 'date',
        'approved_date'     => 'date',
        'disbursed_date'    => 'date',
    ];

    // ── Relationships ──────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function disbursementAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'disbursement_account_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(JournalHeader::class, 'journal_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(StaffLoanRepayment::class);
    }

    // ── Helpers ────────────────────────────────────────────

    public function getOutstandingBalanceAttribute(): float
    {
        return (float) $this->repayments()
            ->where('status', '!=', 'paid')
            ->sum('total_amount');
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->repayments()
            ->where('status', 'paid')
            ->sum('paid_amount');
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->repayments()->where('status', '!=', 'paid')->count() === 0;
    }
}
