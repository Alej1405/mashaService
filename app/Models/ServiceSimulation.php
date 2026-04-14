<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceSimulation extends Model
{
    protected $fillable = [
        'empresa_id',
        'service_design_id',
        'nombre',
        'package_nombre',
        'cantidad',
        'precio_sin_iva',
        'margen_porcentaje',
        'dias_entrega',
        'meta_ganancia',
        'costo_total',
        'ingreso_neto',
        'utilidad_bruta',
        'utilidad_neta',
        'margen_bruto',
        'margen_neto',
        'roi',
        'payback_dias',
        'estado',
    ];

    protected $casts = [
        'cantidad'         => 'decimal:2',
        'precio_sin_iva'   => 'decimal:4',
        'margen_porcentaje' => 'decimal:2',
        'costo_total'      => 'decimal:2',
        'ingreso_neto'     => 'decimal:2',
        'utilidad_bruta'   => 'decimal:2',
        'utilidad_neta'    => 'decimal:2',
        'margen_bruto'     => 'decimal:2',
        'margen_neto'      => 'decimal:2',
        'roi'              => 'decimal:2',
    ];

    public function serviceDesign(): BelongsTo
    {
        return $this->belongsTo(ServiceDesign::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
