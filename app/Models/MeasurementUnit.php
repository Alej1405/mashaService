<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\HasEmpresa;

class MeasurementUnit extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'abreviatura',
        'tipo',
        'factor',
        'activo',
    ];

    protected $casts = [
        'factor' => 'decimal:8',
        'activo' => 'boolean',
    ];

    /** ¿Esta unidad y la otra pertenecen a la misma familia (volumen, longitud, …)? */
    public function esCompatibleCon(MeasurementUnit $otra): bool
    {
        return $this->tipo !== null && $this->tipo === $otra->tipo;
    }

    /**
     * Convierte una cantidad expresada en ESTA unidad a la unidad destino.
     * Devuelve null si no son de la misma familia (conversión imposible).
     * Ej: 20 L → mL  = 20 * (1000 / 1) = 20000.
     */
    public function convertir(float $cantidad, MeasurementUnit $destino): ?float
    {
        if (! $this->esCompatibleCon($destino)) {
            return null;
        }

        return $cantidad * ((float) $this->factor / (float) $destino->factor);
    }
}
