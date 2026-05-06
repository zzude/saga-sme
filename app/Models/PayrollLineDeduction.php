<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PayrollLineDeduction extends Model
{
    protected $fillable = [
        'payroll_line_id', 'component', 'amount',
        'rate_used', 'ceiling_applied', 'taxable_income',
    ];
    protected function casts(): array
    {
        return [
            'amount'          => 'decimal:2',
            'rate_used'       => 'decimal:4',
            'ceiling_applied' => 'boolean',
            'taxable_income'  => 'decimal:2',
        ];
    }
    public function payrollLine(): BelongsTo { return $this->belongsTo(PayrollLine::class); }
}
