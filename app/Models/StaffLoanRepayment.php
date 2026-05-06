<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffLoanRepayment extends Model
{
    protected $fillable = [
        'company_id',
        'staff_loan_id',
        'installment_no',
        'due_date',
        'principal_amount',
        'interest_amount',
        'total_amount',
        'paid_amount',
        'paid_date',
        'balance_after',
        'status',
        'journal_id',
        'payroll_run_id',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:2',
        'interest_amount'  => 'decimal:2',
        'total_amount'     => 'decimal:2',
        'paid_amount'      => 'decimal:2',
        'balance_after'    => 'decimal:2',
        'due_date'         => 'date',
        'paid_date'        => 'date',
    ];

    // ── Relationships ──────────────────────────────────────

    public function loan(): BelongsTo
    {
        return $this->belongsTo(StaffLoan::class, 'staff_loan_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(JournalHeader::class, 'journal_id');
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    // ── Helpers ────────────────────────────────────────────

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'pending' && $this->due_date->isPast();
    }
}
