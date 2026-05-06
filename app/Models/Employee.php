<?php
// ── Employee.php ──────────────────────────────────────────────────────────
namespace App\Models;
use App\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Employee extends Model
{
    use HasCompanyScope;
    protected $fillable = [
        'company_id', 'employee_no', 'name', 'ic_no', 'email', 'phone',
        'gender', 'date_of_birth', 'date_joined', 'date_resigned',
        'position', 'department', 'employment_type', 'basic_salary',
        'epf_no', 'socso_no', 'income_tax_no',
        'marital_status', 'children_count', 'is_active',
        'bank_name', 'bank_account_no', 'created_by', 'updated_by',
    ];
    protected function casts(): array
    {
        return [
            'date_of_birth'  => 'date',
            'date_joined'    => 'date',
            'date_resigned'  => 'date',
            'basic_salary'   => 'decimal:2',
            'is_active'      => 'boolean',
            'children_count' => 'integer',
        ];
    }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function payrollLines(): HasMany { return $this->hasMany(PayrollLine::class); }
    public function isActive(): bool { return $this->is_active && is_null($this->date_resigned); }
}
