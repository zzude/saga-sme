<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashAdvanceSettlement extends Model
{
    protected $fillable = [
        'company_id',
        'cash_advance_id',
        'settlement_type',
        'amount',
        'settlement_date',
        'reference_no',
        'journal_id',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'amount'          => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function cashAdvance(): BelongsTo
    {
        return $this->belongsTo(CashAdvance::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(JournalHeader::class, 'journal_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
