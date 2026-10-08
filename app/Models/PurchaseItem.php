<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'inventory_item_id',
        'quantity',
        'unit_price',
        'aplica_iva',
        'subtotal',
        'iva_monto',
        'total_item',
        'descripcion',
        'codigo_proveedor',
        'producto_proveedor_id',
    ];


    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'subtotal' => 'decimal:4',
        'iva_monto' => 'decimal:4',
        'total_item' => 'decimal:4',
        'aplica_iva' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Las líneas de un comprobante electrónico conservan el subtotal (con
            // descuento) y el IVA del XML: la tarifa no siempre es 15 %.
            if ($model->producto_proveedor_id) {
                return;
            }
            $model->subtotal = $model->quantity * $model->unit_price;
            $model->iva_monto = $model->aplica_iva ? $model->subtotal * 0.15 : 0;
            $model->total_item = $model->subtotal + $model->iva_monto;
        });

        static::updating(function ($model) {
            // Las líneas de un comprobante electrónico conservan el subtotal (con
            // descuento) y el IVA del XML: la tarifa no siempre es 15 %.
            if ($model->producto_proveedor_id) {
                return;
            }
            $model->subtotal = $model->quantity * $model->unit_price;
            $model->iva_monto = $model->aplica_iva ? $model->subtotal * 0.15 : 0;
            $model->total_item = $model->subtotal + $model->iva_monto;
        });
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function productoProveedor(): BelongsTo
    {
        return $this->belongsTo(ProductoProveedor::class);
    }
}
