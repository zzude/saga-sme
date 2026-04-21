<?php

namespace App\Models;

use App\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankReconciliation extends Model
{
    use HasCompanyScope;

    protected $fillable = [
        'company_id',
        'account_id',
        'period_id',
        'statement_date',
        'statement_balance',
        'status',
        'notes',
        'completed_by',
        'completed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'statement_date'    => 'date',
            'statement_balance' => 'decimal:2',
            'completed_at'      => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function statementLines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class, 'reconciliation_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BankReconciliationItem::class, 'reconciliation_id');
    }

    // Calculated balances
    public function clearedBalance(): float
    {
        return (float) $this->items()
            ->where('status', 'cleared')
            ->join('journal_lines', 'journal_lines.id', '=', 'bank_reconciliation_items.journal_line_id')
            ->selectRaw('SUM(journal_lines.debit) - SUM(journal_lines.credit) as net')
            ->value('net');
    }

    public function difference(): float
    {
        return (float) $this->statement_balance - $this->clearedBalance();
    }

    public function isReconciled(): bool
    {
        return abs($this->difference()) < 0.01;
    }
}