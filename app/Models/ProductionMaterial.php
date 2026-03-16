<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionMaterial extends Model
{
    protected $guarded = [];

    protected $casts = [
        'cantidad_consumida' => 'decimal:4',
        'costo_unitario' => 'decimal:4',
        'costo_total' => 'decimal:4',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->costo_total = $model->cantidad_consumida * $model->costo_unitario;
        });

        // Actualizar el costo total de la orden al guardar materiales
        static::saved(function ($model) {
            $order = $model->productionOrder;
            if ($order) {
                $order->update([
                    'costo_total' => $order->materials()->sum('costo_total')
                ]);
            }
        });
        
        static::deleted(function ($model) {
            $order = $model->productionOrder;
            if ($order) {
                $order->update([
                    'costo_total' => $order->materials()->sum('costo_total')
                ]);
            }
        });
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
