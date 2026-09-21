<?php

namespace App\Filament\Admin\Resources\SitioHeroResource\Pages;

use App\Filament\Admin\Resources\SitioHeroResource;
use App\Support\SitioPropio;
use Filament\Resources\Pages\CreateRecord;

class CreateSitioHero extends CreateRecord
{
    protected static string $resource = SitioHeroResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SitioPropio::conEmpresa($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
