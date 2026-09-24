<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Un activo fijo **es** un ítem de inventario, no otra cosa.
 *
 * El inventario ya los clasifica con `type = 'activo_fijo'`; tener además una
 * tabla propia dejó una con el ítem real y la otra vacía, y la depreciación
 * leyendo la vacía. Este modelo es una vista del inventario con las columnas
 * contables que le hacían falta.
 *
 * Marco: skill `contabilidad-ec` — un dato vive en un solo sitio.
 */
class ActivoFijo extends InventoryItem
{
    protected $table = 'inventory_items';

    /** El tipo que el inventario usa para distinguirlos. */
    public const TIPO = 'activo_fijo';

    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('activoFijo', fn (Builder $q) => $q->where('type', self::TIPO));
        static::creating(fn (self $a) => $a->type = self::TIPO);
    }

    /** El costo depreciable: lo que costó menos lo que se espera recuperar. */
    public function getBaseDepreciableAttribute(): float
    {
        return max((float) ($this->costo_promedio ?: $this->purchase_price) - (float) $this->valor_residual, 0);
    }

    /** Línea recta: la misma cuota todos los meses de su vida útil. */
    public function getCuotaMensualAttribute(): float
    {
        return $this->vida_util_meses > 0
            ? round($this->base_depreciable / $this->vida_util_meses, 2)
            : 0.0;
    }

    /** Lo que queda por depreciar; nunca baja del valor residual. */
    public function getValorEnLibrosAttribute(): float
    {
        return round((float) ($this->costo_promedio ?: $this->purchase_price)
            - (float) $this->depreciacion_acumulada, 2);
    }

    public function getDepreciableAttribute(): bool
    {
        return $this->vida_util_meses > 0
            && $this->depreciacion_acumulada < $this->base_depreciable;
    }

    public function depreciaciones()
    {
        return $this->hasMany(Depreciacion::class, 'inventory_item_id');
    }
}
