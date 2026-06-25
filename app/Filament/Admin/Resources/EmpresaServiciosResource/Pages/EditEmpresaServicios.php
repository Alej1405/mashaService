<?php

namespace App\Filament\Admin\Resources\EmpresaServiciosResource\Pages;

use App\Filament\Admin\Resources\EmpresaServiciosResource;
use Filament\Resources\Pages\EditRecord;

class EditEmpresaServicios extends EditRecord
{
    protected static string $resource = EmpresaServiciosResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
