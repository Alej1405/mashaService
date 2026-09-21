<?php

namespace App\Filament\Admin\Resources\SitioLogoResource\Pages;

use App\Filament\Admin\Resources\SitioLogoResource;
use App\Support\SitioPropio;
use Filament\Resources\Pages\CreateRecord;

class CreateSitioLogo extends CreateRecord
{
    protected static string $resource = SitioLogoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SitioPropio::conEmpresa($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
