<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LogisticsBillingRequest extends Model
{
    protected $table = 'logistics_billing_requests';

    protected $fillable = [
        'empresa_id',
        'package_id',
        'store_customer_id',
        'numero_nota_venta',
        'token',
        'subtotal_0',
        'subtotal_15',
        'iva',
        'total',
        'items',
        'billing_type',
        'billing_company_id',
        'billing_nombre',
        'billing_ruc',
        'billing_direccion',
        'estado',
        'accepted_channel',
        'accepted_at',
        'notas',
        'sale_id',
        'verificado_por',
        'verificado_at',
    ];

    protected $casts = [
        'items'        => 'array',
        'subtotal_0'   => 'decimal:2',
        'subtotal_15'  => 'decimal:2',
        'iva'          => 'decimal:2',
        'total'        => 'decimal:2',
        'accepted_at'   => 'datetime',
        'verificado_at' => 'datetime',
    ];

    const ESTADOS = [
        'pendiente'  => ['label' => 'Pendiente de aceptación', 'color' => 'warning'],
        'aceptado'   => ['label' => 'Aceptado',               'color' => 'success'],
        'rechazado'  => ['label' => 'Rechazado',              'color' => 'danger'],
        'facturado'  => ['label' => 'Por cobrar',             'color' => 'info'],
        'cobrado'    => ['label' => 'Cobrado',                'color' => 'primary'],
    ];

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(LogisticsPackage::class, 'package_id');
    }

    public function storeCustomer(): BelongsTo
    {
        return $this->belongsTo(StoreCustomer::class);
    }

    public function billingCompany(): BelongsTo
    {
        return $this->belongsTo(StoreCustomerCompany::class, 'billing_company_id');
    }

    // ── Factory ───────────────────────────────────────────────────────────────

    /**
     * Crea (o devuelve existente) la nota de venta para un paquete.
     */
    public static function crearParaPaquete(LogisticsPackage $package): self
    {
        // Si ya existe una pendiente o aceptada, la devuelve
        $existing = self::where('package_id', $package->id)
            ->whereIn('estado', ['pendiente', 'aceptado'])
            ->first();

        if ($existing) {
            return $existing;
        }

        // Calcular líneas e importes
        $impOrigen           = (float) ($package->impuestos_amazon ?? 0);
        $impAduana           = ($package->impuestos_paga_empresa ? (float) ($package->impuestos_aduana ?? 0) : 0);
        $cargoNacional       = (float) ($package->cobro_nacionalizacion ?? 0);
        $cargoTransporte     = (float) ($package->cobro_transporte_interno ?? 0);
        $cargoOtro           = (float) ($package->cobro_otro ?? 0);

        // Impuestos (origen/aduana): paso al cliente sin IVA
        // Servicios (cobro + cargos): gravan 15% IVA
        $base0  = $impOrigen + $impAduana;
        $base15 = max(0, (float) ($package->monto_cobro ?? 0) - $impAduana)
                  + $cargoNacional + $cargoTransporte + $cargoOtro;

        $subtotal0  = $base0;
        $subtotal15 = $base15;
        $iva        = round($subtotal15 * 0.15, 2);
        $total      = round($subtotal0 + $subtotal15 + $iva, 2);

        $items  = [];
        $codigo = 1;

        // Servicio de importación (base gravable 15%)
        $servicioBase = max(0, (float) ($package->monto_cobro ?? 0) - ($impOrigen + $impAduana));
        if ($servicioBase > 0) {
            $items[] = [
                'codigo'      => str_pad($codigo++, 3, '0', STR_PAD_LEFT),
                'descripcion' => 'Servicio de importación courier'
                    . ($package->numero_tracking ? ' — ' . $package->numero_tracking : ''),
                'cantidad'    => 1,
                'precio'      => round($servicioBase, 2),
                'iva_pct'     => 15,
                'total'       => round($servicioBase, 2),
            ];
        }

        if ($impOrigen > 0) {
            $items[] = [
                'codigo'      => str_pad($codigo++, 3, '0', STR_PAD_LEFT),
                'descripcion' => 'Impuestos de origen',
                'cantidad'    => 1,
                'precio'      => $impOrigen,
                'iva_pct'     => 0,
                'total'       => $impOrigen,
            ];
        }
        if ($impAduana > 0) {
            $items[] = [
                'codigo'      => str_pad($codigo++, 3, '0', STR_PAD_LEFT),
                'descripcion' => 'Impuestos de aduana / liquidación',
                'cantidad'    => 1,
                'precio'      => $impAduana,
                'iva_pct'     => 0,
                'total'       => $impAduana,
            ];
        }
        if ($cargoNacional > 0) {
            $items[] = [
                'codigo'      => str_pad($codigo++, 3, '0', STR_PAD_LEFT),
                'descripcion' => 'Nacionalización',
                'cantidad'    => 1,
                'precio'      => $cargoNacional,
                'iva_pct'     => 15,
                'total'       => $cargoNacional,
            ];
        }
        if ($cargoTransporte > 0) {
            $items[] = [
                'codigo'      => str_pad($codigo++, 3, '0', STR_PAD_LEFT),
                'descripcion' => 'Transporte interno',
                'cantidad'    => 1,
                'precio'      => $cargoTransporte,
                'iva_pct'     => 15,
                'total'       => $cargoTransporte,
            ];
        }
        if ($cargoOtro > 0) {
            $items[] = [
                'codigo'      => str_pad($codigo++, 3, '0', STR_PAD_LEFT),
                'descripcion' => $package->cobro_otro_descripcion ?: 'Otros cargos',
                'cantidad'    => 1,
                'precio'      => $cargoOtro,
                'iva_pct'     => 15,
                'total'       => $cargoOtro,
            ];
        }

        // Fallback si no hay ítems
        if (empty($items)) {
            $items[] = [
                'codigo'      => '001',
                'descripcion' => 'Servicio de importación',
                'cantidad'    => 1,
                'precio'      => 0,
                'iva_pct'     => 15,
                'total'       => 0,
            ];
        }

        // Número secuencial NV-YYYY-#####
        $year  = now()->year;
        $last  = self::where('empresa_id', $package->empresa_id)
                     ->whereYear('created_at', $year)
                     ->max('id') ?? 0;
        $seq   = str_pad($last + 1, 5, '0', STR_PAD_LEFT);
        $numero = "NV-{$year}-{$seq}";

        return self::create([
            'empresa_id'       => $package->empresa_id,
            'package_id'       => $package->id,
            'store_customer_id' => $package->store_customer_id,
            'numero_nota_venta' => $numero,
            'token'            => Str::random(48),
            'subtotal_0'       => $subtotal0,
            'subtotal_15'      => $subtotal15,
            'iva'              => $iva,
            'total'            => $total,
            'items'            => $items,
            'estado'           => 'pendiente',
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function aceptar(string $channel, string $billingType, ?StoreCustomerCompany $company = null, ?StoreCustomer $customer = null): void
    {
        if ($billingType === 'company' && $company) {
            $nombre    = $company->nombre;
            $ruc       = $company->ruc;
            $direccion = $company->direccion;
            $companyId = $company->id;
        } else {
            $nombre    = $customer ? $customer->nombre_completo : $this->storeCustomer->nombre_completo;
            $ruc       = $customer ? ($customer->cedula_ruc ?? null) : ($this->storeCustomer->cedula_ruc ?? null);
            $direccion = null;
            $companyId = null;
        }

        $this->update([
            'billing_type'       => $billingType,
            'billing_company_id' => $companyId,
            'billing_nombre'     => $nombre,
            'billing_ruc'        => $ruc,
            'billing_direccion'  => $direccion,
            'estado'             => 'aceptado',
            'accepted_channel'   => $channel,
            'accepted_at'        => now(),
        ]);
    }

    public function getAcceptUrl(): string
    {
        return url('/tienda/' . $this->empresa->slug . '/billing/' . $this->token);
    }
}
