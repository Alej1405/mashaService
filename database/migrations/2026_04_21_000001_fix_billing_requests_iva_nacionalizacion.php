<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\LogisticsBillingRequest;
use App\Models\LogisticsPackage;

return new class extends Migration
{
    public function up(): void
    {
        LogisticsBillingRequest::whereIn('estado', ['pendiente', 'aceptado'])->each(function (LogisticsBillingRequest $billing) {
            $package = LogisticsPackage::find($billing->package_id);
            if (! $package) {
                return;
            }

            $impOrigen       = (float) ($package->impuestos_amazon ?? 0);
            $impAduana       = ($package->impuestos_paga_empresa ? (float) ($package->impuestos_aduana ?? 0) : 0);
            $cargoNacional   = (float) ($package->cobro_nacionalizacion ?? 0);
            $cargoTransporte = (float) ($package->cobro_transporte_interno ?? 0);
            $cargoOtro       = (float) ($package->cobro_otro ?? 0);

            $base0  = $impOrigen + $impAduana;
            $base15 = max(0, (float) ($package->monto_cobro ?? 0) - $impAduana)
                      + $cargoNacional + $cargoTransporte + $cargoOtro;

            $iva   = round($base15 * 0.15, 2);
            $total = round($base0 + $base15 + $iva, 2);

            // Actualizar iva_pct en el JSON de items
            $items = $billing->items ?? [];
            $items = array_map(function (array $item) {
                $desc = strtolower($item['descripcion'] ?? '');
                if (str_contains($desc, 'nacionalización') || str_contains($desc, 'nacionalizacion')
                    || str_contains($desc, 'transporte') || str_contains($desc, 'otros cargos')
                    || str_contains($desc, 'servicio de importación') || str_contains($desc, 'servicio de importacion')) {
                    $item['iva_pct'] = 15;
                } elseif (str_contains($desc, 'impuesto')) {
                    $item['iva_pct'] = 0;
                }
                return $item;
            }, $items);

            $billing->updateQuietly([
                'subtotal_0'  => $base0,
                'subtotal_15' => $base15,
                'iva'         => $iva,
                'total'       => $total,
                'items'       => $items,
            ]);
        });
    }

    public function down(): void
    {
        // No reversible
    }
};
