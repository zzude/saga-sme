<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LocalOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'lo_number',
        'lo_date',
        'title',
        'purchase_requisition_id',
        'sebut_harga_id',
        'tender_id',
        'vendor_id',
        'vendor_name',
        'ptj_id',
        'program_code',
        'aktiviti_code',
        'objek_sebagai',
        'project_id',
        'budget_item_id',
        'annual_budget_id',
        'warrant_item_id',
        'lo_amount',
        'received_amount',
        'invoiced_amount',
        'paid_amount',
        'encumbrance_posted',
        'encumbrance_posted_at',
        'encumbrance_released',
        'encumbrance_released_at',
        'delivery_date_required',
        'delivery_date_actual',
        'delivery_address',
        'payment_terms_days',
        'terms_conditions',
        'status',
        'approved_by',
        'approved_at',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'lo_date'                   => 'date',
        'delivery_date_required'    => 'date',
        'delivery_date_actual'      => 'date',
        'encumbrance_posted'        => 'boolean',
        'encumbrance_posted_at'     => 'datetime',
        'encumbrance_released'      => 'boolean',
        'encumbrance_released_at'   => 'datetime',
        'approved_at'               => 'datetime',
        'lo_amount'                 => 'decimal:2',
        'received_amount'           => 'decimal:2',
        'invoiced_amount'           => 'decimal:2',
        'paid_amount'               => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            if (auth()->check() && auth()->user()->company_id) {
                $query->where('company_id', auth()->user()->company_id);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Encumbrance logic
    // -------------------------------------------------------------------------

    /**
     * Commit encumbrance to budget_item when LO is approved.
     * Checks available balance before committing.
     */
    public function commitEncumbrance(): bool
    {
        if ($this->encumbrance_posted) {
            return true; // already committed
        }

        $budgetItem = $this->budgetItem;
        if (!$budgetItem) {
            throw new \RuntimeException('Budget item not set on Local Order.');
        }

        $available = $budgetItem->balance_amount - $budgetItem->encumbered_amount;

        if ($this->lo_amount > $available) {
            throw new \RuntimeException(
                sprintf(
                    'Baki peruntukan tidak mencukupi. Tersedia: RM %s, Diperlukan: RM %s',
                    number_format($available, 2),
                    number_format($this->lo_amount, 2)
                )
            );
        }

        DB::transaction(function () use ($budgetItem) {
            // Lock budget_item row to prevent race condition
            $budgetItem->lockForUpdate()->find($budgetItem->id);

            $budgetItem->increment('encumbered_amount', $this->lo_amount);

            $this->update([
                'encumbrance_posted'    => true,
                'encumbrance_posted_at' => now(),
                'status'                => 'approved',
            ]);
        });

        return true;
    }

    /**
     * Release encumbrance when GRN is posted.
     * Actual spending is recorded; encumbrance freed.
     * Called by GoodsReceivedNote after posting.
     *
     * @param float $actualAmount — actual GRN accepted amount
     */
    public function releaseEncumbrance(float $actualAmount): void
    {
        if ($this->encumbrance_released) {
            return;
        }

        DB::transaction(function () use ($actualAmount) {
            $budgetItem = BudgetItem::lockForUpdate()->find($this->budget_item_id);

            // Release the encumbrance
            $budgetItem->decrement('encumbered_amount', $this->lo_amount);

            // Record actual spending
            $budgetItem->increment('actual_spent', $actualAmount);

            $this->update([
                'encumbrance_released'    => true,
                'encumbrance_released_at' => now(),
                'received_amount'         => $actualAmount,
            ]);
        });
    }

    /**
     * Cancel LO — release encumbrance without recording spend.
     */
    public function cancelAndReleaseEncumbrance(): void
    {
        if ($this->encumbrance_posted && !$this->encumbrance_released) {
            DB::transaction(function () {
                $budgetItem = BudgetItem::lockForUpdate()->find($this->budget_item_id);
                $budgetItem->decrement('encumbered_amount', $this->lo_amount);

                $this->update([
                    'encumbrance_released'    => true,
                    'encumbrance_released_at' => now(),
                    'status'                  => 'cancelled',
                ]);
            });
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
    public static function generateLoNumber(): string
    {
        $year = now()->year;
        $last = static::withoutGlobalScope('company')
            ->whereYear('lo_date', $year)
            ->lockForUpdate()
            ->max('lo_number');

        $seq = $last ? (int) substr($last, -5) + 1 : 1;
        return sprintf('LO-%s-%05d', $year, $seq);
    }

    public function getOutstandingAmountAttribute(): float
    {
        return (float) $this->lo_amount - (float) $this->paid_amount;
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->items->every(fn ($item) => $item->quantity_pending <= 0);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------
    public function items(): HasMany
    {
        return $this->hasMany(LoItem::class);
    }

    public function goodsReceivedNotes(): HasMany
    {
        return $this->hasMany(GoodsReceivedNote::class);
    }

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function sebutHarga(): BelongsTo
    {
        return $this->belongsTo(SebutHarga::class, 'sebut_harga_id');
    }

    public function tender(): BelongsTo
    {
        return $this->belongsTo(Tender::class);
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
