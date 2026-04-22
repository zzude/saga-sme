<?php

namespace App\Models;

use App\Traits\HasCompanyScope;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillPayment extends Model
{
    use HasCompanyScope, LogsActivityTrait;

    protected $fillable = [
        'company_id',
        'bill_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_no',
        'bank_account_id',
        'journal_header_id',
        'remarks',
        'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount'       => 'decimal:2',
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'bank_account_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(JournalHeader::class, 'journal_header_id');
    }
}