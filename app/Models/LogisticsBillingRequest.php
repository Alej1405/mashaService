<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
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
        $impOrigen = (float) ($package->impuestos_amazon ?? 0);
        $impAduana = ($package->impuestos_paga_empresa ? (float) ($package->impuestos_aduana ?? 0) : 0);
        $pesoKg    = (float) ($package->peso_kg ?? 0);

        // ── Cargos del diseño de servicio (chargeConfigs) ─────────────────────
        $chargeItems  = [];
        $cargosBase0  = 0.0;
        $cargosBase15 = 0.0;

        $servicePackage = $package->servicePackage;
        if ($servicePackage) {
            foreach ($servicePackage->chargeConfigs()->where('activo', true)->get() as $cfg) {
                $monto = $cfg->tipo === 'peso' && $pesoKg > 0
                    ? round((float) $cfg->monto * $pesoKg, 2)
                    : (float) $cfg->monto;

                if ($monto <= 0) {
                    continue;
                }

                $chargeItems[] = [
                    'nombre'          => $cfg->nombre,
                    'monto'           => $monto,
                    'iva_pct'         => (int) $cfg->iva_pct,
                    'tipo'            => $cfg->tipo,
                    'precio_unitario' => (float) $cfg->monto,
                ];

                if ((int) $cfg->iva_pct === 0) {
                    $cargosBase0 += $monto;
                } else {
                    $cargosBase15 += $monto;
                }
            }
        }

        // ── Cargos extras del embarque (distribuidos por paquete) ─────────────
        $cargosEmbarque = [];
        $ceBase0        = 0.0;
        $ceBase15       = 0.0;

        foreach ($package->shipments()->with('charges')->get() as $shipment) {
            if ($shipment->charges->isEmpty()) {
                continue;
            }
            $pkgCount = max(1, \Illuminate\Support\Facades\DB::table('logistics_shipment_packages')
                ->where('shipment_id', $shipment->id)
                ->count());

            foreach ($shipment->charges as $charge) {
                $monto = round((float) $charge->monto / $pkgCount, 2);
                if ($monto <= 0) {
                    continue;
                }
                $etiqueta = $pkgCount > 1
                    ? $charge->descripcion . ' (1/' . $pkgCount . ' paquetes)'
                    : $charge->descripcion;

                $cargosEmbarque[] = [
                    'descripcion' => $etiqueta,
                    'monto'       => $monto,
                    'iva_pct'     => (int) $charge->iva_pct,
                ];

                if ((int) $charge->iva_pct === 0) {
                    $ceBase0 += $monto;
                } else {
                    $ceBase15 += $monto;
                }
            }
        }

        // Impuestos (origen/aduana): paso al cliente sin IVA
        // Cargos de servicio y embarque: gravan según su iva_pct configurado
        $base0  = $impOrigen + $impAduana + $cargosBase0 + $ceBase0;
        $base15 = max(0, (float) ($package->monto_cobro ?? 0) - $impAduana)
                  + $cargosBase15 + $ceBase15;

        $subtotal0  = $base0;
        $subtotal15 = $base15;
        $iva        = round($subtotal15 * 0.15, 2);
        $total      = round($subtotal0 + $subtotal15 + $iva, 2);

        $items  = [];
        $codigo = 1;

        // Convierte kg a la unidad de cobro configurada en el servicio
        $convertirPeso = function (float $kg, string $unidad): float {
            return match ($unidad) {
                'lb' => round($kg * 2.20462, 3),
                'oz' => round($kg * 35.274,  3),
                'g'  => round($kg * 1000,    2),
                't'  => round($kg / 1000,    4),
                default => $kg,
            };
        };

        // Servicio de importación (base gravable 15%)
        $servicioBase = max(0, (float) ($package->monto_cobro ?? 0) - ($impOrigen + $impAduana));
        if ($servicioBase > 0) {
            $cantServicio   = 1;
            $precioServicio = round($servicioBase, 2);
            $unidadServicio = null;

            if ($servicePackage && $servicePackage->base_cobro === 'peso' && $pesoKg > 0) {
                $unidadCobro    = $servicePackage->unidad_cobro ?? 'kg';
                $cantServicio   = $convertirPeso($pesoKg, $unidadCobro);
                $precioServicio = round((float) ($servicePackage->precio_estimado ?? 0), 4);
                $unidadServicio = $unidadCobro;
            }

            $items[] = [
                'codigo'      => str_pad($codigo++, 3, '0', STR_PAD_LEFT),
                'descripcion' => 'Servicio de importación courier'
                    . ($package->numero_tracking ? ' — ' . $package->numero_tracking : ''),
                'cantidad'    => $cantServicio,
                'precio'      => $precioServicio,
                'iva_pct'     => 15,
                'total'       => round($servicioBase, 2),
                'unidad'      => $unidadServicio,
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
                'unidad'      => null,
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
                'unidad'      => null,
            ];
        }

        // ── Cargos del diseño de servicio ────────────────────────────────────
        foreach ($chargeItems as $ci) {
            $esPeso  = $ci['tipo'] === 'peso';
            $items[] = [
                'codigo'      => str_pad($codigo++, 3, '0', STR_PAD_LEFT),
                'descripcion' => $ci['nombre'],
                'cantidad'    => $esPeso ? $pesoKg : 1,
                'precio'      => $ci['precio_unitario'],
                'iva_pct'     => $ci['iva_pct'],
                'total'       => $ci['monto'],
                'unidad'      => $esPeso ? 'kg' : 'trámite',
            ];
        }

        // ── Cargos extras del embarque ────────────────────────────────────────
        foreach ($cargosEmbarque as $ce) {
            $items[] = [
                'codigo'      => str_pad($codigo++, 3, '0', STR_PAD_LEFT),
                'descripcion' => $ce['descripcion'],
                'cantidad'    => 1,
                'precio'      => $ce['monto'],
                'iva_pct'     => $ce['iva_pct'],
                'total'       => $ce['monto'],
                'unidad'      => 'trámite',
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
