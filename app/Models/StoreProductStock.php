<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Origen de stock de un producto de tienda (pivote store_products ↔ inventory_items).
 * "De dónde sale el stock del producto". El stock disponible se LEE de
 * inventory_items.stock_actual; aquí solo se guarda la relación y la equivalencia.
 * Sin producción.
 */
class StoreProductStock extends Model
{
    use HasEmpresa;

    protected $table = 'store_product_stock';

    protected $fillable = [
        'empresa_id',
        'store_product_id',
        'inventory_item_id',
        'cantidad',
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
}
