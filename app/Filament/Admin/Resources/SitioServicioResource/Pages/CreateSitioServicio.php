<?php

namespace App\Filament\Admin\Resources\SitioServicioResource\Pages;

use App\Filament\Admin\Resources\SitioServicioResource;
use App\Support\SitioPropio;
use Filament\Resources\Pages\CreateRecord;

class CreateSitioServicio extends CreateRecord
{
    protected static string $resource = SitioServicioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SitioPropio::conEmpresa($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
