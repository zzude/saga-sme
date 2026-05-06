<?php
// ── PayrollLine.php ───────────────────────────────────────────────────────
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PayrollLine extends Model
{
    protected $fillable = [
        'payroll_run_id', 'employee_id',
        'basic_salary', 'allowances', 'gross_salary',
        'total_employee_deduction', 'total_employer_cost', 'net_salary',
        'stat_year', 'marital_status', 'children_count',
    ];
    protected function casts(): array
    {
        return [
            'basic_salary'             => 'decimal:2',
            'allowances'               => 'decimal:2',
            'gross_salary'             => 'decimal:2',
            'total_employee_deduction' => 'decimal:2',
            'total_employer_cost'      => 'decimal:2',
            'net_salary'               => 'decimal:2',
            'children_count'           => 'integer',
            'stat_year'                => 'integer',
        ];
    }
    public function payrollRun(): BelongsTo { return $this->belongsTo(PayrollRun::class); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function deductions(): HasMany { return $this->hasMany(PayrollLineDeduction::class); }

    /** Get a specific deduction amount by component */
    public function deduction(string $component): float
    {
        return (float) $this->deductions->firstWhere('component', $component)?->amount ?? 0;
    }
}
