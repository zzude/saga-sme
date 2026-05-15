<?php
// ─────────────────────────────────────────────
// WarrantItem.php
// ─────────────────────────────────────────────
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarrantItem extends Model
{
    protected $fillable = [
        'warrant_allocation_id',
        'budget_item_id',
        'company_id',
        'warrant_amount',
        'used_amount',
        'balance_amount',
        'notes',
    ];

    protected $casts = [
        'warrant_amount' => 'decimal:2',
        'used_amount'    => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public function warrantAllocation(): BelongsTo
    {
        return $this->belongsTo(WarrantAllocation::class);
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
