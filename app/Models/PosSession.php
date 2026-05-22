<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class PosSession extends Model
{
    protected $fillable = [
        'company_id', 'user_id',
        'opened_at', 'closed_at',
        'opening_cash', 'closing_cash',
        'total_sales', 'total_transactions',
        'status', 'notes',
    ];

    protected $casts = [
        'opened_at'    => 'datetime',
        'closed_at'    => 'datetime',
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'total_sales'  => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            if (auth()->check() && auth()->user()->company_id) {
                $query->where('company_id', auth()->user()->company_id);
            }
        });
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PosTransaction::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
