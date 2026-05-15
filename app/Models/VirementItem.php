<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirementItem extends Model
{
    protected $fillable = [
        'virement_id',
        'company_id',
        'direction',
        'budget_item_id',
        'amount',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function virement(): BelongsTo
    {
        return $this->belongsTo(Virement::class);
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isFrom(): bool
    {
        return $this->direction === 'from';
    }

    public function isTo(): bool
    {
        return $this->direction === 'to';
    }
}
