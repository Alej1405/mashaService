<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LogisticsPackage extends Model
{
    use HasEmpresa;

    protected $table = 'logistics_packages';

    protected $fillable = [
        'empresa_id',
        'bodega_id',
        'store_customer_id',
        'numero_tracking',
        'referencia',
        'descripcion',
        'peso_kg',
        'largo_cm',
        'ancho_cm',
        'alto_cm',
        'valor_declarado',
        'moneda',
        'estado',
        'notas',
        'fecha_recepcion_bodega',
    ];

    protected $casts = [
        'peso_kg'               => 'decimal:3',
        'largo_cm'              => 'decimal:2',
        'ancho_cm'              => 'decimal:2',
        'alto_cm'               => 'decimal:2',
        'valor_declarado'       => 'decimal:2',
        'fecha_recepcion_bodega' => 'date',
    ];

    public const ESTADOS = [
        'registrado' => 'Registrado',
        'en_bodega'  => 'En Bodega',
        'asignado'   => 'Asignado a embarque',
        'entregado'  => 'Entregado',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(LogisticsBodega::class, 'bodega_id');
    }

    public function storeCustomer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\StoreCustomer::class, 'store_customer_id');
    }

    public function shipments(): BelongsToMany
    {
        return $this->belongsToMany(
            LogisticsShipment::class,
            'logistics_shipment_packages',
            'package_id',
            'shipment_id'
        )->withTimestamps();
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(LogisticsDocument::class, 'documentable');
    }

    /** Peso volumétrico en kg (largo × ancho × alto / 5000) */
    public function getPesoVolumetricoAttribute(): ?float
    {
        if ($this->largo_cm && $this->ancho_cm && $this->alto_cm) {
            return round(($this->largo_cm * $this->ancho_cm * $this->alto_cm) / 5000, 3);
        }
        return null;
    }
}
