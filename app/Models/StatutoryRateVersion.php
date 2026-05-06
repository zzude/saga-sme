<?php
// ── StatutoryRateVersion.php ──────────────────────────────────────────────
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StatutoryRateVersion extends Model
{
    protected $fillable = [
        'year', 'type', 'rate',
        'ceiling_salary', 'ceiling_amount', 'effective_from',
    ];
    protected function casts(): array
    {
        return [
            'year'           => 'integer',
            'rate'           => 'decimal:4',
            'ceiling_salary' => 'decimal:2',
            'ceiling_amount' => 'decimal:2',
            'effective_from' => 'date',
        ];
    }
    /** Get rate for a given year and type */
    public static function getRate(int $year, string $type): ?self
    {
        return static::where('year', $year)->where('type', $type)->first();
    }
}
