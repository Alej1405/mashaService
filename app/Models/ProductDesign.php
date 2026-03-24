<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductDesign extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'categoria',
        'propuesta_valor',
        'notas_estrategicas',
        'activo',
        'tiene_multiples_presentaciones',
        'capacidad_instalada_mensual',
        'dias_laborales_mes',
        'num_personas',
        'costo_mano_obra_persona',
    ];

    protected $casts = [
        'activo'                          => 'boolean',
        'tiene_multiples_presentaciones'  => 'boolean',
        'capacidad_instalada_mensual'     => 'decimal:2',
        'costo_mano_obra_persona'         => 'decimal:2',
    ];

    public function presentations(): HasMany
    {
        return $this->hasMany(ProductPresentation::class);
    }

    public function productionSteps(): HasMany
    {
        return $this->hasMany(ProductProductionStep::class)->orderBy('orden');
    }

    public function indirectCosts(): HasMany
    {
        return $this->hasMany(ProductIndirectCost::class);
    }
}
