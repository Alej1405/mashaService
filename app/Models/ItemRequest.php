<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemRequest extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'requested_by',
        'nombre',
        'tipo',
        'descripcion',
        'unidad_medida_sugerida',
        'estado',
        'notas_admin',
        'inventory_item_id',
    ];

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
