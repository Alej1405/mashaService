<?php

namespace App\Filament\Resources\EmpresaServiciosResource\Pages;

use App\Filament\Resources\EmpresaServiciosResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmpresaServicios extends CreateRecord
{
    protected static string $resource = EmpresaServiciosResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Empresa creada correctamente';
    }
}
