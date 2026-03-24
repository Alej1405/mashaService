<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductPresentation extends Model
{
    protected $fillable = [
        'product_design_id',
        'nombre',
        'measurement_unit_id',
        'activa',
        'cantidad_minima_produccion',
        'margen_objetivo',
        'pvp_estimado',
        'precio_distribuidor',
        'margen_distribuidor',
        'cantidad_minima_distribuidor',
    ];

    protected $casts = [
        'activa'                       => 'boolean',
        'cantidad_minima_produccion'   => 'decimal:4',
        'margen_objetivo'              => 'decimal:2',
        'pvp_estimado'                 => 'decimal:4',
        'precio_distribuidor'          => 'decimal:4',
        'margen_distribuidor'          => 'decimal:2',
        'cantidad_minima_distribuidor' => 'integer',
    ];

    public function productDesign(): BelongsTo
    {
        return $this->belongsTo(ProductDesign::class);
    }

    public function formulaLines(): HasMany
    {
        return $this->hasMany(ProductFormulaLine::class, 'presentation_id');
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class);
    }
}
