<?php

namespace App\Filament\Admin\Resources\SitioTestimonioResource\Pages;

use App\Filament\Admin\Resources\SitioTestimonioResource;
use App\Support\SitioPropio;
use Filament\Resources\Pages\CreateRecord;

class CreateSitioTestimonio extends CreateRecord
{
    protected static string $resource = SitioTestimonioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SitioPropio::conEmpresa($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
