<?php

namespace App\Filament\Admin\Resources\SitioArticuloResource\Pages;

use App\Filament\Admin\Resources\SitioArticuloResource;
use App\Support\SitioPropio;
use Filament\Resources\Pages\CreateRecord;

class CreateSitioArticulo extends CreateRecord
{
    protected static string $resource = SitioArticuloResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SitioPropio::conEmpresa($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
