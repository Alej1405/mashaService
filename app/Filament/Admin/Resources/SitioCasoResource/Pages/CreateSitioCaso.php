<?php

namespace App\Filament\Admin\Resources\SitioCasoResource\Pages;

use App\Filament\Admin\Resources\SitioCasoResource;
use App\Support\SitioPropio;
use Filament\Resources\Pages\CreateRecord;

class CreateSitioCaso extends CreateRecord
{
    protected static string $resource = SitioCasoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SitioPropio::conEmpresa($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
