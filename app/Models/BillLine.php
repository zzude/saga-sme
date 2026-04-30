<?php
namespace App\Models;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class BillLine extends Model
{
    protected $fillable = [
        'bill_id',
        'sort_order',
        'description',
        'account_id',
        'quantity',
        'unit_price',
        'foreign_unit_price',
        'base_unit_price',
        'amount',
        'tax_amount',
        'line_total',
        'foreign_line_total',
        'base_line_total',
    ];
    protected function casts(): array
    {
        return [
            'quantity'           => 'decimal:2',
            'unit_price'         => 'decimal:2',
            'foreign_unit_price' => 'decimal:2',
            'base_unit_price'    => 'decimal:2',
            'amount'             => 'decimal:2',
            'tax_amount'         => 'decimal:2',
            'line_total'         => 'decimal:2',
            'foreign_line_total' => 'decimal:2',
            'base_line_total'    => 'decimal:2',
        ];
    }
    // ── Relationships ──────────────────────────────────────────
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
    // ── Helpers ───────────────────────────────────────────────
    public function calculateAmount(): void
    {
        $this->amount     = $this->quantity * $this->unit_price;
        $this->line_total = $this->amount + $this->tax_amount;
    }

    /** Option B: base_line_total = round(line_total * rate) — avoids double rounding */
    public function calculateFxAmounts(float $rate): void
    {
        $this->foreign_unit_price = $this->unit_price;
        $this->foreign_line_total = $this->line_total;
        $this->base_unit_price    = round((float) $this->unit_price * $rate, 2);  // informational
        $this->base_line_total    = round((float) $this->line_total * $rate, 2);  // GL authoritative
    }
}
