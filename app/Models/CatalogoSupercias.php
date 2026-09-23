<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Una línea del catálogo oficial de la Superintendencia. */
class CatalogoSupercias extends Model
{
    protected $table = 'catalogo_supercias';

    protected $fillable = ['estado', 'columna', 'codigo', 'nombre', 'signo', 'orden', 'nivel'];

    public const ESTADOS = [
        'situacion_financiera' => 'Estado de situación financiera',
        'resultado_integral'   => 'Estado de resultado integral',
        'flujo_efectivo'       => 'Estado de flujo de efectivo',
        'cambios_patrimonio'   => 'Estado de cambios en el patrimonio',
    ];

    /** El nombre del archivo que espera el portal. */
    public const ARCHIVOS = [
        'situacion_financiera' => 'ESTADO_SITUACION_FINANCIERA.txt',
        'resultado_integral'   => 'ESTADO_RESULTADO_INTEGRAL.txt',
        'flujo_efectivo'       => 'ESTADO_FLUJO_EFECTIVO.txt',
        'cambios_patrimonio'   => 'ESTADO_CAMBIOS_PATRIMONIO.txt',
    ];

    public function getEtiquetaAttribute(): string
    {
        return $this->codigo . ' · ' . ($this->nombre ?? 'sin nombre en el catálogo');
    }
}
