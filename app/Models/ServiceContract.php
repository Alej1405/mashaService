<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceContract extends Model
{
    use HasEmpresa;

    protected $fillable = [
        'empresa_id',
        'store_customer_id',
        'service_design_id',
        'nombre_servicio',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'precio',
        'periodicidad',
        'notas',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'precio'       => 'decimal:2',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(StoreCustomer::class, 'store_customer_id');
    }

    public function serviceDesign(): BelongsTo
    {
        return $this->belongsTo(ServiceDesign::class);
    }

    public function isActivo(): bool
    {
        return $this->estado === 'activo';
    }

    public function isVencido(): bool
    {
        return $this->fecha_fin && $this->fecha_fin->isPast();
    }
}
