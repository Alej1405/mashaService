<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceDeliveryStep extends Model
{
    protected $fillable = [
        'service_design_id',
        'orden',
        'nombre',
        'descripcion',
        'tiempo_estimado_horas',
        'responsable',
    ];

    protected $casts = [
        'tiempo_estimado_horas' => 'decimal:2',
    ];

    public function serviceDesign(): BelongsTo
    {
        return $this->belongsTo(ServiceDesign::class);
    }
}
