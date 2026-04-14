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
    ];

    protected $casts = [
        'activo'           => 'boolean',
        'duracion_estimada' => 'decimal:2',
        'margen_objetivo'  => 'decimal:2',
        'precio_estimado'  => 'decimal:4',
        'costo_base'       => 'decimal:4',
    ];

    public function serviceDesign(): BelongsTo
    {
        return $this->belongsTo(ServiceDesign::class);
    }
}
