<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Insumo / materia prima que compone un producto (pivote store_products ↔ inventory_items).
 * "De qué está hecho el producto". Sin producción.
 */
class ProductMaterial extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'store_product_id',
        'inventory_item_id',
        'cantidad',
        'measurement_unit_id',
        'notas',
    ];

    protected $casts = [
        'cantidad' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(StoreProduct::class, 'store_product_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class);
    }
}
