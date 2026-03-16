<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'cantidad' => 'decimal:4',
        'precio_unitario' => 'decimal:4',
        'aplica_iva' => 'boolean',
        'subtotal' => 'decimal:2',
        'iva_monto' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->subtotal = $model->cantidad * $model->precio_unitario;
            
            if ($model->aplica_iva) {
                // IVA 15% según especificación de Ecuador actual
                $model->iva_monto = $model->subtotal * 0.15;
            } else {
                $model->iva_monto = 0;
            }

            $model->total = $model->subtotal + $model->iva_monto;
        });

        // Actualizar totales de la cabecera al guardar/eliminar ítems
        static::saved(function ($model) {
            if ($model->sale) {
                $model->sale->update([
                    'subtotal' => $model->sale->items()->sum('subtotal'),
                    'iva' => $model->sale->items()->sum('iva_monto'),
                    'total' => $model->sale->items()->sum('total'),
                ]);
            }
        });

        static::deleted(function ($model) {
            if ($model->sale) {
                $model->sale->update([
                    'subtotal' => $model->sale->items()->sum('subtotal'),
                    'iva' => $model->sale->items()->sum('iva_monto'),
                    'total' => $model->sale->items()->sum('total'),
                ]);
            }
        });
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
