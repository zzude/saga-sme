<?php

namespace App\Models;

use App\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePayment extends Model
{
    use HasCompanyScope;

    protected $fillable = [
        'company_id',
        'invoice_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_no',
        'bank_account_id',
        'journal_header_id',
        'remarks',
        'received_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount'       => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
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