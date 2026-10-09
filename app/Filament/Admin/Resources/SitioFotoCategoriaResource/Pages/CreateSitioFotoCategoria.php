<?php

namespace App\Filament\Admin\Resources\SitioFotoCategoriaResource\Pages;

use App\Filament\Admin\Resources\SitioFotoCategoriaResource;
use App\Support\SitioPropio;
use Filament\Resources\Pages\CreateRecord;

class CreateSitioFotoCategoria extends CreateRecord
{
    protected static string $resource = SitioFotoCategoriaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SitioPropio::conEmpresa($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
