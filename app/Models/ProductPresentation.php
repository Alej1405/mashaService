<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductPresentation extends Model
{
    protected $fillable = [
        'product_design_id',
        'nombre',
        'codigo_sri',
        'capacidad',
        'inventory_item_id',
        'measurement_unit_id',
        'activa',
        'cantidad_minima_produccion',
        'margen_objetivo',
        'pvp_estimado',
        'costo_estandar',
        'costo_calculado_en',
    ];

    protected $casts = [
        'activa'                    => 'boolean',
        'cantidad_minima_produccion' => 'decimal:4',
        'margen_objetivo'           => 'decimal:2',
        'pvp_estimado'              => 'decimal:4',
        'costo_estandar'            => 'decimal:4',
        'capacidad'                 => 'decimal:4',
        'costo_calculado_en'        => 'datetime',
    ];

    public function productDesign(): BelongsTo
    {
        return $this->belongsTo(ProductDesign::class);
    }

    /**
     * Cada presentación se vende como un producto: con su ítem de inventario y su
     * producto de tienda, que nace sin publicar hasta que marketing lo complete.
     */
    protected static function booted(): void
    {
        static::saved(fn (self $p) => app(\App\Services\PresentacionComoProducto::class)->sincronizar($p));
    }

    /** ¿Otra presentación de la empresa ya usa este código del SRI? */
    public static function codigoSriUsado(string $codigo, int $empresaId, ?int $exceptoId = null): bool
    {
        return static::where('codigo_sri', trim($codigo))
            ->when($exceptoId, fn ($q) => $q->where('id', '!=', $exceptoId))
            ->whereHas('productDesign', fn ($q) => $q->withoutGlobalScopes()->where('empresa_id', $empresaId))
            ->exists();
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function storeProduct(): HasOne
    {
        return $this->hasOne(StoreProduct::class);
    }

    /** El envase de esta presentación; la receta es del producto. */
    public function formulaLines(): HasMany
    {
        return $this->hasMany(ProductFormulaLine::class, 'presentation_id');
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class);
    }
}
