<?php

namespace App\Filament\Admin\Resources\EmpresaServiciosResource\Pages;

use App\Filament\Admin\Resources\EmpresaServiciosResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmpresaServicios extends CreateRecord
{
    protected static string $resource = EmpresaServiciosResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Empresa creada correctamente';
    }
}
