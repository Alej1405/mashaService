<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSimulation extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id', 'product_design_id', 'nombre', 'presentation_nombre',
        'cantidad', 'pvp_sin_iva', 'margen_porcentaje', 'dias_venta', 'meta_ganancia',
        'aplica_ice', 'ice_categoria', 'ice_porcentaje',
        'inversion_real', 'costo_total', 'ingreso_neto',
        'utilidad_bruta', 'utilidad_neta', 'margen_bruto', 'margen_neto',
        'roi', 'payback_dias', 'iva_total', 'ice_total', 'notas', 'estado',
    ];

    protected $casts = [
        'aplica_ice' => 'boolean',
        'cantidad'   => 'decimal:2',
        'roi'        => 'decimal:2',
        'payback_dias' => 'decimal:1',
    ];

    public function productDesign(): BelongsTo
    {
        return $this->belongsTo(ProductDesign::class);
    }
}
