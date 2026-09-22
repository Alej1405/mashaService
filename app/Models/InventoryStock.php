<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cuánto hay de un ítem en una ubicación concreta.
 *
 * inventory_items.stock_actual es el total; esta tabla dice dónde está repartido.
 * Es lo que permite responder "dónde está la sal" sin preguntarle a nadie.
 */
class InventoryStock extends Model
{
    use HasEmpresa;

    protected $table = 'inventory_stocks';

    protected $fillable = ['empresa_id', 'inventory_item_id', 'almacen_id', 'ubicacion_almacen_id', 'cantidad'];

    protected $casts = ['cantidad' => 'decimal:4'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(UbicacionAlmacen::class, 'ubicacion_almacen_id');
    }
}
