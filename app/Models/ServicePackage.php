<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePackage extends Model
{
    protected $fillable = [
        'service_design_id',
        'nombre',
        'descripcion',
        'duracion_estimada',
        'duracion_unidad',
        'activo',
        'margen_objetivo',
        'precio_estimado',
        'costo_base',
        'base_cobro',
        'unidad_cobro',
        'cobro_nacionalizacion',
        'cobro_nacionalizacion_tipo',
        'cobro_transporte_interno',
        'cobro_transporte_interno_tipo',
        'cobro_otro',
        'cobro_otro_tipo',
        'cobro_otro_descripcion',
    ];

    protected $casts = [
        'activo'                   => 'boolean',
        'duracion_estimada'        => 'decimal:2',
        'margen_objetivo'          => 'decimal:2',
        'precio_estimado'          => 'decimal:4',
        'costo_base'               => 'decimal:4',
        'cobro_nacionalizacion'    => 'decimal:2',
        'cobro_transporte_interno' => 'decimal:2',
        'cobro_otro'               => 'decimal:2',
    ];

    public function serviceDesign(): BelongsTo
    {
        return $this->belongsTo(ServiceDesign::class);
    }

    public function chargeConfigs(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(ServiceChargeConfig::class, 'service_package_charge_config');
    }
}
