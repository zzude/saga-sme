<?php
namespace App\Models;
use App\Traits\HasCompanyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PayrollGlMapping extends Model
{
    use HasCompanyScope;
    protected $fillable = ['company_id', 'component', 'account_id'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function account(): BelongsTo { return $this->belongsTo(Account::class); }

    /** Get account_id for a component, for a given company */
    public static function accountFor(int $companyId, string $component): ?int
    {
        return static::where('company_id', $companyId)
            ->where('component', $component)
            ->value('account_id');
    }
}
