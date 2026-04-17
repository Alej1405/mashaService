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
    ];

    protected $casts = [
        'items'        => 'array',
        'subtotal_0'   => 'decimal:2',
        'subtotal_15'  => 'decimal:2',
        'iva'          => 'decimal:2',
        'total'        => 'decimal:2',
        'accepted_at'  => 'datetime',
    ];

    const ESTADOS = [
        'pendiente'  => ['label' => 'Pendiente de aceptación', 'color' => 'warning'],
        'aceptado'   => ['label' => 'Aceptado',               'color' => 'success'],
        'rechazado'  => ['label' => 'Rechazado',              'color' => 'danger'],
        'facturado'  => ['label' => 'Facturado',              'color' => 'info'],
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
        $impAduana  = ($package->impuestos_paga_empresa ? (float) ($package->impuestos_aduana ?? 0) : 0);
        $subtotal0  = $impOrigen + $impAduana;
        $subtotal15 = max(0, (float) ($package->monto_cobro ?? 0) - $subtotal0);
        $iva        = round($subtotal15 * 0.15, 2);
        $total      = round($subtotal0 + $subtotal15 + $iva, 2);

        $items = [];
        if ($subtotal15 > 0) {
            $items[] = [
                'codigo'      => '001',
                'descripcion' => 'Servicio de importación courier'
                    . ($package->numero_tracking ? ' — ' . $package->numero_tracking : ''),
                'cantidad'    => 1,
                'precio'      => round($subtotal15, 2),
                'iva_pct'     => 15,
                'total'       => round($subtotal15, 2),
            ];
        }
        if ($impOrigen > 0) {
            $items[] = [
                'codigo'      => '002',
                'descripcion' => 'Impuestos de origen',
                'cantidad'    => 1,
                'precio'      => $impOrigen,
                'iva_pct'     => 0,
                'total'       => $impOrigen,
            ];
        }
        if ($impAduana > 0) {
            $items[] = [
                'codigo'      => '003',
                'descripcion' => 'Impuestos de aduana / liquidación',
                'cantidad'    => 1,
                'precio'      => $impAduana,
                'iva_pct'     => 0,
                'total'       => $impAduana,
            ];
        }
        // Fallback si monto_cobro = 0 y no hay items
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
