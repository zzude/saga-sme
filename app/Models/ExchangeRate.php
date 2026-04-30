<?php

// app/Models/ExchangeRate.php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class ExchangeRate extends Model
{
    protected $fillable = [
        'rate_date',
        'from_currency',
        'to_currency',
        'rate',
        'source',
        'fetched_at',
        'is_locked',
        'override_reason',
    ];
 
    protected $casts = [
        'rate_date'  => 'date',
        'fetched_at' => 'datetime',
        'is_locked'  => 'boolean',
        'rate'       => 'decimal:8',
    ];
 
    public function fromCurrency()
    {
        return $this->belongsTo(Currency::class, 'from_currency', 'code');
    }
 
    public function toCurrency()
    {
        return $this->belongsTo(Currency::class, 'to_currency', 'code');
    }
}
