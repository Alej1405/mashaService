<?php

namespace App\Filament\Contabilidad\Resources\RetencionResource\Pages;

use App\Filament\Contabilidad\Resources\RetencionResource;
use Filament\Resources\Pages\CreateRecord;

class CrearRetencion extends CreateRecord
{
    protected static string $resource = RetencionResource::class;

    /** Los totales se recalculan de las líneas, no se escriben. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $lineas = collect($data['lineas'] ?? []);
        $data['retenido_renta'] = round($lineas->where('tipo', 'renta')->sum(fn ($l) => ((float) $l['base']) * ((float) $l['porcentaje']) / 100), 2);
        $data['retenido_iva']   = round($lineas->where('tipo', 'iva')->sum(fn ($l) => ((float) $l['base']) * ((float) $l['porcentaje']) / 100), 2);
        $data['total_retenido'] = round($data['retenido_renta'] + $data['retenido_iva'], 2);

        return $data;
    }
}
