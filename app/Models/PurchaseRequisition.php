<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class PurchaseRequisition extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'pr_number',
        'pr_date',
        'title',
        'description',
        'ptj_id',
        'program_code',
        'aktiviti_code',
        'objek_sebagai',
        'project_id',
        'budget_item_id',
        'annual_budget_id',
        'estimated_amount',
        'procurement_method',
        'status',
        'requested_by',
        'approved_by_hod',
        'approved_hod_at',
        'approved_by_finance',
        'approved_finance_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'notes',
    ];

    protected $casts = [
        'pr_date' => 'date',
        'approved_hod_at' => 'datetime',
        'approved_finance_at' => 'datetime',
        'rejected_at' => 'datetime',
        'estimated_amount' => 'decimal:2',
    ];

    // -------------------------------------------------------------------------
    // Global scope
    // -------------------------------------------------------------------------
    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            if (auth()->check() && auth()->user()->company_id) {
                $query->where('company_id', auth()->user()->company_id);
            }
        });

        // Auto-determine procurement method based on estimated amount
        static::creating(function (self $pr) {
            $pr->procurement_method = self::determineProcurementMethod($pr->estimated_amount);
        });

        static::updating(function (self $pr) {
            if ($pr->isDirty('estimated_amount')) {
                $pr->procurement_method = self::determineProcurementMethod($pr->estimated_amount);
            }
        });
    }

    // -------------------------------------------------------------------------
    // Business logic
    // -------------------------------------------------------------------------
    public static function determineProcurementMethod(float $amount): string
    {
        return match (true) {
            $amount < 10000       => 'direct_purchase',
            $amount <= 200000     => 'sebut_harga',
            default               => 'tender_terbuka',
        };
    }

    public static function generatePrNumber(): string
    {
        $year = now()->year;
        $lastPr = static::withoutGlobalScope('company')
            ->whereYear('pr_date', $year)
            ->lockForUpdate()
            ->max('pr_number');

        $seq = $lastPr
            ? (int) substr($lastPr, -5) + 1
            : 1;

        return sprintf('PR-%s-%05d', $year, $seq);
    }

    public function getProcurementMethodLabelAttribute(): string
    {
        return match ($this->procurement_method) {
            'direct_purchase' => 'Pembelian Terus (< RM10k)',
            'sebut_harga'     => 'Sebut Harga (RM10k – RM200k)',
            'tender_terbuka'  => 'Tender Terbuka (> RM200k)',
            'tender_terhad'   => 'Tender Terhad',
            'rundingan_terus' => 'Rundingan Terus',
            default           => $this->procurement_method,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'            => 'Draf',
            'submitted'        => 'Dihantar',
            'approved_hod'     => 'Lulus (Ketua Jabatan)',
            'approved_finance' => 'Lulus (Kewangan)',
            'rejected'         => 'Ditolak',
            'cancelled'        => 'Dibatal',
            'converted_lo'     => 'Ditukar ke LO',
            'converted_sh'     => 'Ditukar ke Sebut Harga',
            'converted_tender' => 'Ditukar ke Tender',
            default            => $this->status,
        };
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, ['submitted', 'approved_hod']);
    }

    public function canBeConverted(): bool
    {
        return $this->status === 'approved_finance';
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------
    public function items(): HasMany
    {
        return $this->hasMany(PrItem::class);
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function annualBudget(): BelongsTo
    {
        return $this->belongsTo(AnnualBudget::class);
    }

    public function localOrders(): HasMany
    {
        return $this->hasMany(LocalOrder::class);
    }

    public function sebuthHargas(): HasMany
    {
        return $this->hasMany(SebutHarga::class);
    }

    public function tenders(): HasMany
    {
        return $this->hasMany(Tender::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
