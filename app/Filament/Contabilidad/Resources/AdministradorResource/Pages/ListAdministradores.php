<?php

namespace App\Filament\Contabilidad\Resources\AdministradorResource\Pages;

use App\Filament\Contabilidad\Resources\AdministradorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdministradores extends ListRecords
{
    protected static string $resource = AdministradorResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Agregar administrador')];
    }
}
