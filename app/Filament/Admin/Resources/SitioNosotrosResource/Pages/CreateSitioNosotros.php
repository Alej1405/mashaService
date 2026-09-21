<?php

namespace App\Filament\Admin\Resources\SitioNosotrosResource\Pages;

use App\Filament\Admin\Resources\SitioNosotrosResource;
use App\Support\SitioPropio;
use Filament\Resources\Pages\CreateRecord;

class CreateSitioNosotros extends CreateRecord
{
    protected static string $resource = SitioNosotrosResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SitioPropio::conEmpresa($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
