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
        'customer_id',
        'service_package_id',
        'cantidad_cobro',
        'monto_cobro',
        'sale_id',
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
        'valor_declarado'        => 'decimal:2',
        'fecha_recepcion_bodega' => 'date',
        'cantidad_cobro'         => 'decimal:4',
        'monto_cobro'            => 'decimal:2',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Sale::class, 'sale_id');
    }

    /**
     * Devuelve el Customer ERP del paquete.
     * Primero intenta desde customer_id directo, luego desde StoreCustomer.
     * Si no existe, lo crea a partir del StoreCustomer.
     */
    public function resolverCustomerErp(): ?\App\Models\Customer
    {
        if ($this->customer_id) {
            return $this->customer;
        }

        $sc = $this->storeCustomer;
        if (! $sc) {
            return null;
        }

        // Si el StoreCustomer ya tiene ERP customer, usar ese
        if ($sc->customer_id) {
            $this->updateQuietly(['customer_id' => $sc->customer_id]);
            return $sc->customer;
        }

        // Disparar el observer manualmente para que cree el Customer ERP
        (new \App\Observers\StoreCustomerObserver())->created($sc);
        $sc->refresh();

        if ($sc->customer_id) {
            $this->updateQuietly(['customer_id' => $sc->customer_id]);
            return $sc->customer;
        }

        return null;
    }

    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ServicePackage::class, 'service_package_id');
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
