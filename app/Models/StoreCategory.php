<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StoreCategory extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'parent_id',
        'nombre',
        'slug',
        'descripcion',
        'imagen',
        'publicado',
        'orden',
    ];

    protected $casts = [
        'publicado' => 'boolean',
        'orden'     => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->slug)) {
                $model->slug = static::uniqueSlug($model->empresa_id, Str::slug($model->nombre));
            }
        });
    }

    private static function uniqueSlug(int $empresaId, string $base): string
    {
        $slug = $base;
        $i    = 1;
        while (static::withoutGlobalScopes()->where('empresa_id', $empresaId)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }
        return $slug;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(StoreProduct::class);
    }
}
