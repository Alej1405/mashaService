<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceIndirectCost extends Model
{
    protected $fillable = [
        'service_design_id',
        'tipo',
        'descripcion',
        'monto_mensual',
        'frecuencia',
    ];

    protected $casts = [
        'monto_mensual' => 'decimal:2',
    ];

    public function serviceDesign(): BelongsTo
    {
        return $this->belongsTo(ServiceDesign::class);
    }
}
