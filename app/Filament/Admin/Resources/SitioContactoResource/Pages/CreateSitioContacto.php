<?php

namespace App\Filament\Admin\Resources\SitioContactoResource\Pages;

use App\Filament\Admin\Resources\SitioContactoResource;
use App\Support\SitioPropio;
use Filament\Resources\Pages\CreateRecord;

class CreateSitioContacto extends CreateRecord
{
    protected static string $resource = SitioContactoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SitioPropio::conEmpresa($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
