<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxCode extends Model
{
    protected $fillable = [
        'company_id',
        'code',
        'description',
        'tax_type',
        'rate',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate'           => 'decimal:2',
            'effective_from' => 'date',
            'effective_to'   => 'date',
            'is_active'      => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getSelectLabelAttribute(): string
    {
        return $this->code . ' — ' . $this->description . ' (' . $this->rate . '%)';
    }

    /**
     * Get effective rate for a given date
     */
    public static function getEffectiveRate(int $companyId, string $code, string $date): float
    {
        $tax = self::where(function($q) use ($companyId) {
                    $q->where('company_id', $companyId)->orWhereNull('company_id');
                })
                ->where('code', $code)
                ->where('is_active', true)
                ->where(function($q) use ($date) {
                    $q->whereNull('effective_from')->orWhere('effective_from', '<=', $date);
                })
                ->where(function($q) use ($date) {
                    $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date);
                })
                ->orderByDesc('effective_from')
                ->first();

        return $tax ? (float) $tax->rate : 0;
    }
}