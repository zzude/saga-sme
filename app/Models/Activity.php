<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Activity extends Model
{
    protected $fillable = [
        'company_id', 'program_id', 'code', 'name', 'description', 'is_active',
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

    public function program(): BelongsTo { return $this->belongsTo(Program::class, 'program_id'); }
}
