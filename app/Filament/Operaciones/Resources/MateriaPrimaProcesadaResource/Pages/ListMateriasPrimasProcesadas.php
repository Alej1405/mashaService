<?php

namespace App\Filament\Operaciones\Resources\MateriaPrimaProcesadaResource\Pages;

use App\Filament\Operaciones\Resources\MateriaPrimaProcesadaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMateriasPrimasProcesadas extends ListRecords
{
    protected static string $resource = MateriaPrimaProcesadaResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
