<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;

class CostoFijo extends Model
{
    use HasEmpresa;

    protected $table = 'costos_fijos';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'categoria',
        'monto',
        'frecuencia',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'monto'  => 'decimal:2',
        'activo' => 'boolean',
    ];

    /** Monto mensual equivalente independientemente de la frecuencia */
    public function getMontoMensualAttribute(): float
    {
        return match ($this->frecuencia) {
            'trimestral' => $this->monto / 3,
            'semestral'  => $this->monto / 6,
            'anual'      => $this->monto / 12,
            default      => (float) $this->monto,
        };
    }
}
