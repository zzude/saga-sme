<?php
// app/Models/Currency.php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class Currency extends Model
{
    protected $primaryKey = 'code';
    protected $keyType    = 'string';
    public    $incrementing = false;
 
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'is_active',
    ];
 
    protected $casts = [
        'is_active'      => 'boolean',
        'decimal_places' => 'integer',
    ];
 
    // Scope: active currencies only
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
 
    // Scope: foreign currencies (exclude MYR)
    public function scopeForeign($query)
    {
        return $query->where('code', '!=', 'MYR');
    }
 
    // Helper: options array for Filament Select
    public static function activeOptions(): array
    {
        return static::active()
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn ($c) => [$c->code => "{$c->code} — {$c->name}"])
            ->toArray();
    }
 
    // Helper: foreign currency options only
    public static function foreignOptions(): array
    {
        return static::active()
            ->foreign()
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn ($c) => [$c->code => "{$c->code} — {$c->name}"])
            ->toArray();
    }
}
 