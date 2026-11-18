<?php

namespace App\Filament\Operaciones\Resources\MateriaPrimaProcesadaResource\Pages;

use App\Filament\Operaciones\Resources\MateriaPrimaProcesadaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMateriaPrimaProcesada extends EditRecord
{
    use \App\Filament\Concerns\RegresaAlListado;

    protected static string $resource = MateriaPrimaProcesadaResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
