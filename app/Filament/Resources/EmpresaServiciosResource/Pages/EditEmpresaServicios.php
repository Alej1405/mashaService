<?php

namespace App\Filament\Resources\EmpresaServiciosResource\Pages;

use App\Filament\Resources\EmpresaServiciosResource;
use Filament\Resources\Pages\EditRecord;

class EditEmpresaServicios extends EditRecord
{
    protected static string $resource = EmpresaServiciosResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
