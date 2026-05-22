<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Ptj extends Model
{
    protected $table = 'ptj';
    protected $fillable = [
        'company_id', 'code', 'name', 'short_name',
        'description', 'head_id', 'is_active',
    ];
    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            if (auth()->check() && auth()->user()->company_id) {
                $query->where('company_id', auth()->user()->company_id);
            }
        });
    }

    public function programs(): HasMany { return $this->hasMany(Program::class, 'ptj_id'); }
    public function head(): BelongsTo { return $this->belongsTo(User::class, 'head_id'); }
}
