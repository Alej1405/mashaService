<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFormulaLine extends Model
{
    protected $fillable = [
        'presentation_id',
        'inventory_item_id',
        'item_request_id',
        'cantidad',
        'measurement_unit_id',
        'es_subproducto_manufacturado',
        'notas',
        'costo_estimado',
    ];

    protected $casts = [
        'cantidad'                    => 'decimal:6',
        'costo_estimado'              => 'decimal:4',
        'es_subproducto_manufacturado' => 'boolean',
    ];

    public function presentation(): BelongsTo
    {
        return $this->belongsTo(ProductPresentation::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function itemRequest(): BelongsTo
    {
        return $this->belongsTo(ItemRequest::class);
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class);
    }
}
