<?php

namespace App\Filament\Resources\EmpresaServiciosResource\Pages;

use App\Filament\Resources\EmpresaServiciosResource;
use Filament\Resources\Pages\ListRecords;

class ListEmpresaServicios extends ListRecords
{
    protected static string $resource = EmpresaServiciosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()->label('Nueva empresa'),
        ];
    }
}
