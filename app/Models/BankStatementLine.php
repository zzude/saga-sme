<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementLine extends Model
{
    protected $fillable = [
        'reconciliation_id',
        'txn_date',
        'reference_no',
        'description',
        'amount',
        'running_balance',
        'matched_journal_line_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'txn_date'        => 'date',
            'amount'          => 'decimal:2',
            'running_balance' => 'decimal:2',
        ];
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function matchedJournalLine(): BelongsTo
    {
        return $this->belongsTo(JournalLine::class, 'matched_journal_line_id');
    }

    public function isMatched(): bool
    {
        return $this->status === 'matched';
    }
}