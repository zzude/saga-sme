<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'description',
        'type',
        'selling_price',
        'cost_price',
        'unit_of_measure',
        'is_sst_applicable',
        'sst_rate',
        'track_inventory',
        'current_stock',
        'reorder_level',
        'income_account_id',
        'expense_account_id',
        'category',
        'is_active',
    ];

    protected $casts = [
        'selling_price'     => 'decimal:2',
        'cost_price'        => 'decimal:2',
        'sst_rate'          => 'decimal:2',
        'current_stock'     => 'decimal:2',
        'reorder_level'     => 'decimal:2',
        'is_sst_applicable' => 'boolean',
        'track_inventory'   => 'boolean',
        'is_active'         => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Global scope — company isolation
    // -------------------------------------------------------------------------
    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            if (auth()->check() && auth()->user()->company_id) {
                $query->where('company_id', auth()->user()->company_id);
            }
        });

        // Auto-set track_inventory based on type
        static::creating(function (self $item) {
            if ($item->type === 'service') {
                $item->track_inventory = false;
            }
        });
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'product' => 'Produk',
            'service' => 'Perkhidmatan',
            'bundle'  => 'Bundle',
            default   => $this->type,
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'product' => 'info',
            'service' => 'success',
            'bundle'  => 'warning',
            default   => 'gray',
        };
    }

    public function isLowStock(): bool
    {
        return $this->track_inventory
            && $this->reorder_level > 0
            && $this->current_stock <= $this->reorder_level;
    }

    public function isOutOfStock(): bool
    {
        return $this->track_inventory && $this->current_stock <= 0;
    }

    // -------------------------------------------------------------------------
    // Stock adjustment
    // -------------------------------------------------------------------------
    public function adjustStock(
        string $type,
        float $quantity,
        float $unitCost = 0,
        string $referenceType = null,
        int $referenceId = null,
        string $referenceNo = null,
        string $notes = null
    ): StockMovement {
        if (!$this->track_inventory) {
            throw new \RuntimeException("Item {$this->code} tidak track inventory.");
        }

        $balanceBefore = $this->current_stock;

        $balanceAfter = match ($type) {
            'in', 'opening', 'adjustment' => $balanceBefore + $quantity,
            'out'                          => $balanceBefore - $quantity,
            default                        => throw new \RuntimeException("Jenis pergerakan tidak sah: {$type}"),
        };

        if ($type === 'out' && $balanceAfter < 0) {
            throw new \RuntimeException("Stok tidak mencukupi untuk {$this->name}. Stok semasa: {$balanceBefore}");
        }

        $movement = StockMovement::create([
            'company_id'     => $this->company_id,
            'item_id'        => $this->id,
            'type'           => $type,
            'quantity'       => $quantity,
            'unit_cost'      => $unitCost,
            'balance_after'  => $balanceAfter,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'reference_no'   => $referenceNo,
            'notes'          => $notes,
            'created_by'     => auth()->id(),
        ]);

        $this->update(['current_stock' => $balanceAfter]);

        return $movement;
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->orderByDesc('created_at');
    }

    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }
}
