<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliationItem extends Model
{
    protected $fillable = [
        'reconciliation_id',
        'journal_line_id',
        'statement_line_id',
        'source_type',
        'status',
        'cleared_at',
    ];

    protected function casts(): array
    {
        return [
            'cleared_at' => 'datetime',
        ];
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class);
    }

    public function journalLine(): BelongsTo
    {
        return $this->belongsTo(JournalLine::class);
    }

    public function statementLine(): BelongsTo
    {
        return $this->belongsTo(BankStatementLine::class);
    }

    public function isCleared(): bool
    {
        return $this->status === 'cleared';
    }
}