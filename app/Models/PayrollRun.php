<?php
namespace App\Models;
use App\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PayrollRun extends Model
{
    use HasCompanyScope;
    protected $fillable = [
        'company_id', 'payroll_period_id', 'period_id', 'reference_no', 'status',
        'total_gross', 'total_employee_deduction', 'total_employer_cost', 'total_net_salary',
        'total_kwsp', 'total_socso', 'total_eis', 'total_pcb',
        'journal_header_id',
        'created_by', 'approved_by', 'approved_at',
        'posted_by', 'posted_at', 'locked_by', 'locked_at',
        'reversal_of_run_id',
    ];
    protected function casts(): array
    {
        return [
            'approved_at'              => 'datetime',
            'posted_at'                => 'datetime',
            'locked_at'                => 'datetime',
            'total_gross'              => 'decimal:2',
            'total_employee_deduction' => 'decimal:2',
            'total_employer_cost'      => 'decimal:2',
            'total_net_salary'         => 'decimal:2',
            'total_kwsp'               => 'decimal:2',
            'total_socso'              => 'decimal:2',
            'total_eis'                => 'decimal:2',
            'total_pcb'                => 'decimal:2',
        ];
    }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function payrollPeriod(): BelongsTo { return $this->belongsTo(PayrollPeriod::class); }
    public function accountingPeriod(): BelongsTo { return $this->belongsTo(AccountingPeriod::class, 'period_id'); }
    public function journal(): BelongsTo { return $this->belongsTo(JournalHeader::class, 'journal_header_id'); }
    public function lines(): HasMany { return $this->hasMany(PayrollLine::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function postedBy(): BelongsTo { return $this->belongsTo(User::class, 'posted_by'); }
    public function reversalOf(): BelongsTo { return $this->belongsTo(PayrollRun::class, 'reversal_of_run_id'); }

    public function isDraft(): bool    { return $this->status === 'draft'; }
    public function isApproved(): bool { return $this->status === 'approved'; }
    public function isPosted(): bool   { return $this->status === 'posted'; }
    public function isLocked(): bool   { return $this->status === 'locked'; }
    public function isEditable(): bool { return in_array($this->status, ['draft']); }
}
