<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un producto dentro de la orden de producción de un pedido, con su lote. */
class ProductionOrderLinea extends Model
{
    protected $table = 'production_order_lineas';

    protected $fillable = [
        'production_order_id', 'store_order_item_id', 'product_presentation_id',
        'inventory_item_id', 'descripcion', 'cantidad', 'lote',
    ];

    protected $casts = ['cantidad' => 'decimal:4'];

    public function orden(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    /** La línea del pedido: de ahí sale la nota del cliente para este producto. */
    public function itemPedido(): BelongsTo
    {
        return $this->belongsTo(StoreOrderItem::class, 'store_order_item_id');
    }

    public function productPresentation(): BelongsTo
    {
        return $this->belongsTo(ProductPresentation::class);
    }
}
