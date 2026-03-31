<?php

namespace App\Models;

use App\Enums\AccountLevel;
use App\Enums\AccountType;
use App\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasCompanyScope;

    protected $fillable = [
        'company_id',
        'parent_id',
        'code',
        'name',
        'type',
        'level',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type'      => AccountType::class,
            'level'     => AccountLevel::class,
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id')->withoutGlobalScopes();
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id')->withoutGlobalScopes();
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePostable($query)
    {
        return $query->where('level', AccountLevel::Account->value);
    }

    public function scopeOfType($query, AccountType $type)
    {
        return $query->where('type', $type->value);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Only Level 3 accounts may receive journal entry lines. */
    public function isPostable(): bool
    {
        return $this->level === AccountLevel::Account;
    }

    /** Display label used in select dropdowns. */
    public function getSelectLabelAttribute(): string
    {
        return $this->code . ' — ' . $this->name;
    }
}
