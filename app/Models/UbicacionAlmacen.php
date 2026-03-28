<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UbicacionAlmacen extends Model
{
    use HasEmpresa;

    protected $table = 'ubicaciones_almacen';

    protected $fillable = [
        'empresa_id',
        'almacen_id',
        'zona_id',
        'codigo_ubicacion',
        'nombre',
        'capacidad_maxima',
        'unidad_capacidad',
        'activo',
    ];

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(ZonaAlmacen::class, 'zona_id');
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * Etiqueta completa: Bodega Principal > Estantería A > A-01-03
     */
    public function getEtiquetaCompletaAttribute(): string
    {
        return implode(' › ', array_filter([
            $this->almacen?->nombre,
            $this->zona?->nombre,
            $this->nombre,
        ]));
    }
}
