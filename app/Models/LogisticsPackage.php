<?php

namespace App\Models;

use App\Traits\HasEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'gastos_envio',
        'impuestos_amazon',
        'impuestos_paga_empresa',
        'impuestos_aduana',
        'cobro_nacionalizacion',
        'cobro_transporte_interno',
        'cobro_otro',
        'cobro_otro_descripcion',
        'moneda',
        'estado',
        'estado_secundario',
        'notas',
        'fecha_recepcion_bodega',
    ];

    protected $casts = [
        'peso_kg'               => 'decimal:3',
        'largo_cm'              => 'decimal:2',
        'ancho_cm'              => 'decimal:2',
        'alto_cm'               => 'decimal:2',
        'valor_declarado'        => 'decimal:2',
        'gastos_envio'           => 'decimal:2',
        'impuestos_amazon'         => 'decimal:2',
        'impuestos_paga_empresa'   => 'boolean',
        'impuestos_aduana'         => 'decimal:2',
        'fecha_recepcion_bodega'   => 'date',
        'cantidad_cobro'              => 'decimal:4',
        'monto_cobro'                 => 'decimal:2',
        'cobro_nacionalizacion'       => 'decimal:2',
        'cobro_transporte_interno'    => 'decimal:2',
        'cobro_otro'                  => 'decimal:2',
    ];

    // Estados principales (columnas del Kanban)
    public const ESTADOS = [
        'embarque_solicitado' => ['label' => 'Embarque Solicitado',                  'color' => '#64748b'],
        'registrado'          => ['label' => 'Registrado',                            'color' => '#6366f1'],
        'en_aduana'           => ['label' => 'Arribo e Inicio de Proceso en Aduana', 'color' => '#f59e0b'],
        'finalizado_aduana'   => ['label' => 'Finalizado en Aduana',                 'color' => '#06b6d4'],
        'pago_servicios'      => ['label' => 'Pago de Servicios',                    'color' => '#f97316'],
        'en_entrega'          => ['label' => 'En Coordinación de Entrega',           'color' => '#22c55e'],
    ];

    // Estados secundarios agrupados por su estado principal
    public const ESTADOS_SECUNDARIOS = [
        'embarque_solicitado' => [
            'embarque_confirmado' => ['label' => 'Embarque Confirmado',  'color' => '#22c55e'],
            'embarque_retraso'    => ['label' => 'Embarque con Retraso', 'color' => '#ef4444'],
        ],
        'registrado' => [
            'arribo_miami' => ['label' => 'Arribo a Miami', 'color' => '#3b82f6'],
            'con_retraso'  => ['label' => 'Con Retraso',    'color' => '#ef4444'],
        ],
        'en_aduana' => [
            'declaracion_transmitida' => ['label' => 'Declaración Transmitida', 'color' => '#f97316'],
            'aforo_automatico'        => ['label' => 'Aforo Automático',        'color' => '#10b981'],
            'aforo_documental'        => ['label' => 'Aforo Documental',        'color' => '#f97316'],
            'aforo_fisico'            => ['label' => 'Aforo Físico',            'color' => '#ef4444'],
            'liquidada'               => ['label' => 'Liquidada',               'color' => '#06b6d4'],
        ],
        'finalizado_aduana' => [
            'en_ubicacion_despacho' => ['label' => 'En Ubicación para Despacho', 'color' => '#8b5cf6'],
            'en_despacho'           => ['label' => 'En Despacho',               'color' => '#3b82f6'],
        ],
        'pago_servicios' => [
            'factura_enviada' => ['label' => 'Factura Enviada',  'color' => '#f97316'],
            'pago_pendiente'  => ['label' => 'Pago Pendiente',   'color' => '#ef4444'],
            'pago_confirmado' => ['label' => 'Pago Confirmado',  'color' => '#22c55e'],
        ],
        'en_entrega' => [
            'retiro_oficina'    => ['label' => 'Retiro de Oficina',    'color' => '#6366f1'],
            'entrega_domicilio' => ['label' => 'Entrega en Domicilio', 'color' => '#3b82f6'],
            'entregado'         => ['label' => 'Entregado',            'color' => '#15803d'],
        ],
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

    public function items(): HasMany
    {
        return $this->hasMany(LogisticsPackageItem::class, 'logistics_package_id');
    }

    public function billingRequests(): HasMany
    {
        return $this->hasMany(LogisticsBillingRequest::class, 'package_id');
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
