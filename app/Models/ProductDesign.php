<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\StoreCategory;
use App\Models\InventoryItem;

class ProductDesign extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'categoria',
        'store_category_id',
        'inventory_item_id',
        'precio_distribuidor',
        'margen_distribuidor',
        'cantidad_minima_distribuidor',
        'propuesta_valor',
        'notas_estrategicas',
        'activo',
        'publicado_catalogo',
        'tiene_multiples_presentaciones',
        'capacidad_instalada_mensual',
        'dias_laborales_mes',
        'num_personas',
        'costo_mano_obra_persona',
    ];

    protected $casts = [
        'activo'                          => 'boolean',
        'publicado_catalogo'              => 'boolean',
        'tiene_multiples_presentaciones'  => 'boolean',
        'capacidad_instalada_mensual'     => 'decimal:2',
        'costo_mano_obra_persona'         => 'decimal:2',
        'precio_distribuidor'             => 'decimal:4',
        'margen_distribuidor'             => 'decimal:2',
        'cantidad_minima_distribuidor'    => 'integer',
    ];

    public function storeCategory(): BelongsTo
    {
        return $this->belongsTo(StoreCategory::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function presentations(): HasMany
    {
        return $this->hasMany(ProductPresentation::class);
    }

    public function productionSteps(): HasMany
    {
        return $this->hasMany(ProductProductionStep::class)->orderBy('orden');
    }

    public function indirectCosts(): HasMany
    {
        return $this->hasMany(ProductIndirectCost::class);
    }
}
