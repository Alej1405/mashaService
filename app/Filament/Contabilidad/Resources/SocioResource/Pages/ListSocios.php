<?php

namespace App\Filament\Contabilidad\Resources\SocioResource\Pages;

use App\Filament\Contabilidad\Resources\SocioResource;
use App\Models\Socio;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListSocios extends ListRecords
{
    protected static string $resource = SocioResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Agregar socio')];
    }

    public function getSubheading(): ?string
    {
        $suma = (float) Socio::withoutGlobalScopes()
            ->where('empresa_id', Filament::getTenant()->id)->where('activo', true)->sum('participacion');

        return abs($suma - 100) < 0.01
            ? 'La participación suma 100 %.'
            : 'La participación suma ' . rtrim(rtrim(number_format($suma, 2, ',', '.'), '0'), ',') . ' %: debería sumar 100 %.';
    }
}
