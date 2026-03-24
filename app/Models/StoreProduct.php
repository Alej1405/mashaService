<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StoreProduct extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'inventory_item_id',
        'store_category_id',
        'nombre',
        'slug',
        'descripcion',
        'precio_venta',
        'imagen_principal',
        'galeria',
        'publicado',
        'destacado',
        'orden',
    ];

    protected $casts = [
        'precio_venta' => 'decimal:4',
        'galeria'      => 'array',
        'publicado'    => 'boolean',
        'destacado'    => 'boolean',
        'orden'        => 'integer',
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

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function storeCategory(): BelongsTo
    {
        return $this->belongsTo(StoreCategory::class);
    }
}
