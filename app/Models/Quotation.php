<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Quotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'quotation_number',
        'revision',
        'quotation_ref',
        'quotation_date',
        'valid_until',
        'title',
        'customer_id',
        'customer_name',
        'customer_address',
        'attention_to',
        'subtotal',
        'discount_amount',
        'taxable_amount',
        'sst_amount',
        'total_amount',
        'sst_applicable',
        'sst_rate',
        'payment_terms_days',
        'terms_conditions',
        'notes',
        'remarks',
        'status',
        'converted_invoice_id',
        'converted_at',
        'converted_by',
        'parent_quotation_id',
        'is_latest_revision',
        'created_by',
        'sent_by',
        'sent_at',
        'accepted_by',
        'accepted_at',
    ];

    protected $casts = [
        'quotation_date'   => 'date',
        'valid_until'      => 'date',
        'converted_at'     => 'datetime',
        'sent_at'          => 'datetime',
        'accepted_at'      => 'datetime',
        'sst_applicable'   => 'boolean',
        'is_latest_revision' => 'boolean',
        'subtotal'         => 'decimal:2',
        'discount_amount'  => 'decimal:2',
        'taxable_amount'   => 'decimal:2',
        'sst_amount'       => 'decimal:2',
        'total_amount'     => 'decimal:2',
        'sst_rate'         => 'decimal:2',
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
    }

    // -------------------------------------------------------------------------
    // Number generation
    // -------------------------------------------------------------------------
    public static function generateQuotationNumber(int $revision = 0): array
    {
        // ref = QT-2026-00001 (same across all revisions)
        // number = QT-2026-00001 (Rev 0) or QT-2026-00001-R1 (Rev 1+)
        $year = now()->year;

        $last = static::withoutGlobalScope('company')
            ->where('revision', 0)
            ->whereYear('quotation_date', $year)
            ->lockForUpdate()
            ->max('quotation_ref');

        $seq = $last ? (int) substr($last, -5) + 1 : 1;
        $ref = sprintf('QT-%s-%05d', $year, $seq);

        $number = $revision > 0
            ? "{$ref}-R{$revision}"
            : $ref;

        return ['ref' => $ref, 'number' => $number];
    }

    // -------------------------------------------------------------------------
    // Revision — create new revision from existing quotation
    // -------------------------------------------------------------------------
    public function createRevision(): self
    {
        if (!in_array($this->status, ['sent', 'rejected'])) {
            throw new \RuntimeException('Hanya quotation status Sent atau Rejected boleh direvisi.');
        }

        return DB::transaction(function () {
            // Mark current as no longer latest
            $this->update(['is_latest_revision' => false]);

            $newRevision = $this->revision + 1;
            $newNumber   = "{$this->quotation_ref}-R{$newRevision}";

            // Clone header
            $newQt = $this->replicate();
            $newQt->quotation_number    = $newNumber;
            $newQt->revision            = $newRevision;
            $newQt->quotation_date      = now()->toDateString();
            $newQt->valid_until         = now()->addDays(30)->toDateString();
            $newQt->status              = 'draft';
            $newQt->parent_quotation_id = $this->id;
            $newQt->is_latest_revision  = true;
            $newQt->converted_invoice_id = null;
            $newQt->converted_at        = null;
            $newQt->converted_by        = null;
            $newQt->sent_at             = null;
            $newQt->sent_by             = null;
            $newQt->accepted_at         = null;
            $newQt->accepted_by         = null;
            $newQt->created_by          = auth()->id();
            $newQt->save();

            // Clone items
            foreach ($this->items as $item) {
                $newItem = $item->replicate();
                $newItem->quotation_id = $newQt->id;
                $newItem->save();
            }

            return $newQt;
        });
    }

    // -------------------------------------------------------------------------
    // Convert to Invoice — 1 click
    // -------------------------------------------------------------------------
    public function convertToInvoice(): Invoice
    {
        if ($this->status !== 'accepted') {
            throw new \RuntimeException('Hanya quotation status Accepted boleh ditukar ke Invoice.');
        }

        if ($this->converted_invoice_id) {
            throw new \RuntimeException('Quotation ini sudah ditukar ke Invoice.');
        }

        return DB::transaction(function () {
            // Build invoice via InvoiceService
            $invoiceService = app(InvoiceService::class);

            $invoiceData = [
                'company_id'          => $this->company_id,
                'customer_id'         => $this->customer_id,
                'invoice_date'        => now()->toDateString(),
                'due_date'            => now()->addDays($this->payment_terms_days)->toDateString(),
                'reference'           => $this->quotation_number,
                'notes'               => $this->remarks,
                'sst_applicable'      => $this->sst_applicable,
                'sst_rate'            => $this->sst_rate,
                'payment_terms_days'  => $this->payment_terms_days,
                'terms_conditions'    => $this->terms_conditions,
                'quotation_id'        => $this->id,  // backlink
                'items'               => $this->items->map(fn ($item) => [
                    'description'      => $item->description,
                    'detail'           => $item->detail,
                    'unit_of_measure'  => $item->unit_of_measure,
                    'quantity'         => $item->quantity,
                    'unit_price'       => $item->unit_price,
                    'discount_percent' => $item->discount_percent,
                    'is_sst_applicable'=> $item->is_sst_applicable,
                    'sst_rate'         => $item->sst_rate,
                    'item_id'          => $item->item_id,
                ])->toArray(),
            ];

            $invoice = $invoiceService->create($invoiceData);

            // Mark quotation as converted
            $this->update([
                'status'               => 'converted',
                'converted_invoice_id' => $invoice->id,
                'converted_at'         => now(),
                'converted_by'         => auth()->id(),
            ]);

            return $invoice;
        });
    }

    // -------------------------------------------------------------------------
    // Totals recalculation — call after saving items
    // -------------------------------------------------------------------------
    public function recalculateTotals(): void
    {
        $items = $this->items()->get();

        $subtotal       = $items->sum('gross_amount');
        $discountAmount = $items->sum('discount_amount');
        $taxableAmount  = $subtotal - $discountAmount;
        $sstAmount      = $items->sum('sst_amount');
        $totalAmount    = $taxableAmount + $sstAmount;

        $this->update([
            'subtotal'        => $subtotal,
            'discount_amount' => $discountAmount,
            'taxable_amount'  => $taxableAmount,
            'sst_amount'      => $sstAmount,
            'total_amount'    => $totalAmount,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_until < now()->toDateString()
            && !in_array($this->status, ['converted', 'cancelled', 'accepted']);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'Draf',
            'sent'      => 'Dihantar',
            'accepted'  => 'Diterima',
            'rejected'  => 'Ditolak',
            'expired'   => 'Tamat Tempoh',
            'cancelled' => 'Dibatal',
            'converted' => 'Ditukar ke Invois',
            default     => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'gray',
            'sent'      => 'info',
            'accepted'  => 'success',
            'rejected'  => 'danger',
            'expired'   => 'warning',
            'cancelled' => 'gray',
            'converted' => 'success',
            default     => 'gray',
        };
    }

    public function getRevisionLabelAttribute(): string
    {
        return $this->revision === 0 ? 'Asal' : "Semakan {$this->revision}";
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------
    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('line_no');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    public function parentQuotation(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_quotation_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_quotation_id')->orderBy('revision');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
