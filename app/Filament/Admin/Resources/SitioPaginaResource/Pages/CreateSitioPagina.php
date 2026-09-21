<?php

namespace App\Filament\Admin\Resources\SitioPaginaResource\Pages;

use App\Filament\Admin\Resources\SitioPaginaResource;
use App\Support\SitioPropio;
use Filament\Resources\Pages\CreateRecord;

class CreateSitioPagina extends CreateRecord
{
    protected static string $resource = SitioPaginaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SitioPropio::conEmpresa($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
