<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceDesign extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'categoria',
        'descripcion_servicio',
        'propuesta_valor',
        'notas_estrategicas',
        'activo',
        'publicado_catalogo',
        'tiene_multiples_paquetes',
        'unidad_capacidad',
        'capacidad_mensual',
        'dias_laborales_mes',
        'num_personas',
        'costo_persona_mes',
        'precio_revendedor',
        'margen_revendedor',
        'cantidad_minima_revendedor',
    ];

    protected $casts = [
        'activo'                   => 'boolean',
        'publicado_catalogo'       => 'boolean',
        'tiene_multiples_paquetes' => 'boolean',
        'capacidad_mensual'        => 'decimal:2',
        'costo_persona_mes'        => 'decimal:2',
        'precio_revendedor'        => 'decimal:4',
        'margen_revendedor'        => 'decimal:2',
        'cantidad_minima_revendedor' => 'integer',
    ];

    public function packages(): HasMany
    {
        return $this->hasMany(ServicePackage::class);
    }

    public function deliverySteps(): HasMany
    {
        return $this->hasMany(ServiceDeliveryStep::class)->orderBy('orden');
    }

    public function indirectCosts(): HasMany
    {
        return $this->hasMany(ServiceIndirectCost::class);
    }

    public function simulations(): HasMany
    {
        return $this->hasMany(ServiceSimulation::class);
    }
}
