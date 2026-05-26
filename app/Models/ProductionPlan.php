<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductionPlan extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'product_simulation_id',
        'designable_type',
        'designable_id',
        'tipo_produccion',
        'num_parciales',
        'fecha_inicio',
        'fecha_fin',
        'estado',
    ];

    protected $casts = [
        'fecha_inicio'  => 'date',
        'fecha_fin'     => 'date',
        'num_parciales' => 'integer',
    ];

    public function simulation(): BelongsTo
    {
        return $this->belongsTo(ProductSimulation::class, 'product_simulation_id');
    }

    public function productionOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function designable(): MorphTo
    {
        return $this->morphTo();
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
