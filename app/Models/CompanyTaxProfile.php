<?php

namespace App\Models;

use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyTaxProfile extends Model
{
    use LogsActivityTrait;

    protected $fillable = [
        'company_id',
        'tax_reg_no',
        'tax_type',
        'effective_date',
        'is_registered',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'is_registered'  => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}