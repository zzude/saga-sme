<?php

namespace App\Models;

use App\Traits\HasCompanyScope;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalHeader extends Model
{
    use HasCompanyScope, LogsActivityTrait;

    protected $fillable = [
        'company_id',
        'period_id',
        'reference_no',
        'date',
        'status',
        'source_type',
        'summary_text',
        'created_by',
        'posted_by',
        'posted_at',
        'voided_by',
        'voided_at',
        'void_reason',
        'reversed_from_id',
    ];

    protected function casts(): array
    {
        return [
            'date'      => 'date',
            'posted_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'journal_header_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function reversedFrom(): BelongsTo
    {
        return $this->belongsTo(JournalHeader::class, 'reversed_from_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(JournalHeader::class, 'reversed_from_id');
    }

    // ── Helpers ────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    /** Total debit — must equal total credit */
    public function totalDebit(): float
    {
        return (float) $this->lines->sum('debit');
    }

    public function totalCredit(): float
    {
        return (float) $this->lines->sum('credit');
    }

    public function isBalanced(): bool
    {
        return abs($this->totalDebit() - $this->totalCredit()) < 0.01;
    }
}