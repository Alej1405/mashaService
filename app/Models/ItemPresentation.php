<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemPresentation extends Model
{
    protected $table = 'item_presentations';

    protected $fillable = [
        'inventory_item_id',
        'empresa_id',
        'nombre',
        'factor_conversion',
        'es_unidad_base',
        'activo',
    ];

    protected $casts = [
        'factor_conversion' => 'decimal:6',
        'es_unidad_base'    => 'boolean',
        'activo'            => 'boolean',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
