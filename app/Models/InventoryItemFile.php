<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItemFile extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'nombre_archivo',
        'descripcion',
        'path',
        'tipo',
        'size',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
