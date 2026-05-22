<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ExpenditureObject extends Model
{
    protected $fillable = [
        'company_id', 'parent_id', 'code', 'name',
        'level', 'category', 'description', 'is_active',
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

    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id'); }

    public function getLevelLabelAttribute(): string
    {
        return match($this->level) {
            'objek'     => 'Objek',
            'sub_objek' => 'Sub Objek',
            default     => $this->level,
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'mengurus'    => 'Mengurus',
            'pembangunan' => 'Pembangunan',
            default       => $this->category,
        };
    }
}
