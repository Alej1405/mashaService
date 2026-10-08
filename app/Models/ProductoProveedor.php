<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Qué es, en esta empresa, un producto que factura un proveedor (por su código en
 * el XML): un ítem de inventario o un tipo de gasto. Se configura una vez; desde
 * ahí cada compra de ese producto entra sola.
 */
class ProductoProveedor extends Model
{
    use HasEmpresa;

    protected $table = 'productos_proveedor';

    protected $fillable = [
        'empresa_id', 'supplier_id', 'codigo', 'descripcion', 'inventory_item_id',
        'tipo_gasto_id', 'ultimo_precio', 'configurado_en', 'configurado_por',
    ];

    protected $casts = [
        'ultimo_precio'  => 'decimal:6',
        'configurado_en' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function tipoGasto(): BelongsTo
    {
        return $this->belongsTo(TipoGasto::class);
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function estaConfigurado(): bool
    {
        return $this->inventory_item_id !== null || $this->tipo_gasto_id !== null;
    }
}
