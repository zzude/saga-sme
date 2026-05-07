<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashAdvance extends Model
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'advance_no',
        'purpose',
        'amount_requested',
        'amount_approved',
        'amount_settled',
        'status',
        'applied_date',
        'approved_date',
        'disbursed_date',
        'due_date',
        'account_id',
        'journal_id',
        'approved_by',
        'disbursed_by',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'applied_date'     => 'date',
        'approved_date'    => 'date',
        'disbursed_date'   => 'date',
        'due_date'         => 'date',
        'amount_requested' => 'decimal:2',
        'amount_approved'  => 'decimal:2',
        'amount_settled'   => 'decimal:2',
    ];

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
        return $this->belongsTo(Account::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(JournalHeader::class, 'journal_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function disbursedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(CashAdvanceSettlement::class);
    }

    public function getOutstandingAmountAttribute(): float
    {
        $approved = (float) ($this->amount_approved ?? $this->amount_requested);
        return $approved - (float) $this->amount_settled;
    }
}
