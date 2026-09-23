<?php

namespace App\Filament\Contabilidad\Resources\RetencionRecibidaResource\Pages;

use App\Filament\Contabilidad\Resources\RetencionRecibidaResource;
use App\Models\RetencionRecibida;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListRetencionesRecibidas extends ListRecords
{
    protected static string $resource = RetencionRecibidaResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Registrar retención')];
    }

    public function getSubheading(): ?string
    {
        $anio = now()->year;
        $total = (float) RetencionRecibida::withoutGlobalScopes()
            ->where('empresa_id', Filament::getTenant()->id)
            ->whereYear('fecha', $anio)->where('tipo', 'renta')->sum('valor');

        return 'Crédito de renta del ' . $anio . ': $ ' . number_format($total, 2, ',', '.')
            . ' · se resta del impuesto causado en el formulario 101.';
    }
}
