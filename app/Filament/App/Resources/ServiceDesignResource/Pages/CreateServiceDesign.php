<?php

namespace App\Filament\App\Resources\ServiceDesignResource\Pages;

use App\Filament\App\Resources\ServiceDesignResource;
use App\Models\ServiceSimulation;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceDesign extends CreateRecord
{
    protected static string $resource = ServiceDesignResource::class;

    protected function afterCreate(): void
    {
        $this->guardarSimulacionSiProcede();
    }

    private function guardarSimulacionSiProcede(): void
    {
        $data = $this->data;

        $nombre   = $data['_sim_nombre'] ?? null;
        $cantidad = (float) ($data['_sim_cantidad'] ?? 0);
        $precio   = (float) ($data['_sim_precio'] ?? 0);

        if (!$nombre || $cantidad <= 0 || $precio <= 0) return;

        $record     = $this->record;
        $incluyeIva = (bool) ($data['_sim_incluye_iva'] ?? false);
        $precioSinIva = $incluyeIva ? round($precio / 1.15, 4) : $precio;

        $capacidad = (float) ($record->capacidad_mensual ?? 0);
        $personas  = (float) ($record->num_personas ?? 0);
        $costoP    = (float) ($record->costo_persona_mes ?? 0);
        $fracMes   = $capacidad > 0 ? $cantidad / $capacidad : 1;

        $totalP     = $personas * $costoP * $fracMes;
        $totalFijos = ServiceDesignResource::costosFijosMensuales() * $fracMes;
        $totalOtros = 0;
        foreach ($record->indirectCosts as $ic) {
            $monto = (float) $ic->monto_mensual;
            $totalOtros += match ($ic->frecuencia) {
                'semanal' => $monto * 4.33 * $fracMes,
                'unico'   => $monto,
                default   => $monto * $fracMes,
            };
        }

        $costoTotal    = $totalP + $totalOtros + $totalFijos;
        $ingresoNeto   = $precioSinIva * $cantidad;
        $utilidadBruta = $ingresoNeto - $costoTotal;
        $margenBruto   = $ingresoNeto > 0 ? ($utilidadBruta / $ingresoNeto) * 100 : 0;
        $roi           = $costoTotal > 0 ? ($utilidadBruta / $costoTotal) * 100 : 0;

        $packageKey = $data['_sim_package_key'] ?? null;
        $packages   = $data['packages'] ?? [];
        $pkgNombre  = null;
        if ($packageKey !== null && isset($packages[$packageKey])) {
            $pkgNombre = $packages[$packageKey]['nombre'] ?? null;
        }

        ServiceSimulation::create([
            'empresa_id'        => Filament::getTenant()->id,
            'service_design_id' => $record->id,
            'nombre'            => $nombre,
            'package_nombre'    => $pkgNombre,
            'cantidad'          => $cantidad,
            'precio_sin_iva'    => $precioSinIva,
            'margen_porcentaje' => (float) ($data['_sim_margen'] ?? 0),
            'dias_entrega'      => (int) ($data['_sim_dias_entrega'] ?? 0) ?: null,
            'meta_ganancia'     => (float) ($data['_sim_meta_ganancia'] ?? 5),
            'costo_total'       => $costoTotal,
            'ingreso_neto'      => $ingresoNeto,
            'utilidad_bruta'    => $utilidadBruta,
            'utilidad_neta'     => $utilidadBruta,
            'margen_bruto'      => $margenBruto,
            'margen_neto'       => $margenBruto,
            'roi'               => $roi,
            'estado'            => $data['_sim_estado'] ?? 'en_proyecto',
        ]);
    }
}
