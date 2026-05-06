<?php
namespace App\Models;
use App\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
class PayrollPeriod extends Model
{
    use HasCompanyScope;
    protected $fillable = [
        'company_id', 'name', 'year', 'month',
        'start_date', 'end_date', 'payment_date', 'status',
    ];
    protected function casts(): array
    {
        return [
            'start_date'   => 'date',
            'end_date'     => 'date',
            'payment_date' => 'date',
            'year'         => 'integer',
            'month'        => 'integer',
        ];
    }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function run(): HasOne { return $this->hasOne(PayrollRun::class); }
    public function isOpen(): bool { return $this->status === 'open'; }
    public function isClosed(): bool { return $this->status === 'closed'; }
}
