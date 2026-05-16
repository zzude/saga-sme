<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GoodsReceivedNote extends Model
{
    use SoftDeletes;

    protected $table = 'goods_received_notes';

    protected $fillable = [
        'company_id',
        'grn_number',
        'local_order_id',
        'received_date',
        'vendor_id',
        'vendor_delivery_note',
        'vendor_delivery_date',
        'received_by',
        'verified_by',
        'verified_at',
        'total_received_amount',
        'encumbrance_released',
        'encumbrance_released_at',
        'is_posted',
        'posted_at',
        'posted_by',
        'journal_id',
        'status',
        'condition_notes',
        'rejection_reason',
    ];

    protected $casts = [
        'received_date'           => 'date',
        'vendor_delivery_date'    => 'date',
        'verified_at'             => 'datetime',
        'encumbrance_released'    => 'boolean',
        'encumbrance_released_at' => 'datetime',
        'is_posted'               => 'boolean',
        'posted_at'               => 'datetime',
        'total_received_amount'   => 'decimal:2',
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
    // Posting — releases encumbrance + creates GL journal
    // -------------------------------------------------------------------------
    public function post(int $postedBy): void
    {
        if ($this->is_posted) {
            throw new \RuntimeException('GRN already posted.');
        }

        DB::transaction(function () use ($postedBy) {
            // 1. Calculate accepted amount from items
            $acceptedAmount = $this->items->sum('accepted_amount');

            $this->update([
                'total_received_amount' => $acceptedAmount,
                'is_posted'             => true,
                'posted_at'             => now(),
                'posted_by'             => $postedBy,
                'status'                => 'posted',
            ]);

            // 2. Update LO received quantities
            foreach ($this->items as $grnItem) {
                $loItem = $grnItem->loItem;
                $loItem->increment('quantity_received', $grnItem->quantity_accepted);
            }

            // 3. Release encumbrance from budget_item via LO
            // encumbered_amount decreases, actual_spent increases
            $localOrder = $this->localOrder;
            $localOrder->releaseEncumbrance($acceptedAmount);

            // 4. Update LO status
            $localOrder->update([
                'received_amount' => $acceptedAmount,
                'status'          => $localOrder->is_fully_received
                    ? 'fully_received'
                    : 'partial_received',
            ]);

            // 5. Create GL journal (stub — ProcurementService will handle full GL)
            // Dr: 22000 Belian Bekalan / Perbelanjaan OS
            // Cr: 21000 Akaun Belum Bayar (Payable)
            // Journal creation delegated to ProcurementService::createGrnJournal()
        });
    }

    public static function generateGrnNumber(): string
    {
        $year = now()->year;
        $last = static::withoutGlobalScope('company')
            ->whereYear('received_date', $year)
            ->lockForUpdate()
            ->max('grn_number');

        $seq = $last ? (int) substr($last, -5) + 1 : 1;
        return sprintf('GRN-%s-%05d', $year, $seq);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------
    public function items(): HasMany
    {
        return $this->hasMany(GrnItem::class);
    }

    public function localOrder(): BelongsTo
    {
        return $this->belongsTo(LocalOrder::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
