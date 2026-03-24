<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductProductionStep extends Model
{
    protected $fillable = [
        'product_design_id',
        'orden',
        'nombre',
        'descripcion',
        'tiempo_estimado_minutos',
    ];

    protected $casts = [
        'orden'                   => 'integer',
        'tiempo_estimado_minutos' => 'integer',
    ];

    public function productDesign(): BelongsTo
    {
        return $this->belongsTo(ProductDesign::class);
    }
}
