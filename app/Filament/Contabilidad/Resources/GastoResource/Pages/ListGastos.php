<?php

namespace App\Filament\Contabilidad\Resources\GastoResource\Pages;

use App\Filament\Contabilidad\Resources\GastoResource;
use App\Models\Gasto;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListGastos extends ListRecords
{
    protected static string $resource = GastoResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Nuevo gasto')];
    }

    public function getSubheading(): ?string
    {
        $empresa = Filament::getTenant();
        $borradores = Gasto::withoutGlobalScopes()->where('empresa_id', $empresa->id)->where('estado', 'borrador')->count();

        return $borradores
            ? "{$borradores} gasto(s) en borrador: sin confirmar no existen para el estado de resultados ni para el 101."
            : 'Todos los gastos están contabilizados.';
    }
}
