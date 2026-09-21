<?php

namespace App\Filament\Admin\Resources\SitioFaqResource\Pages;

use App\Filament\Admin\Resources\SitioFaqResource;
use App\Support\SitioPropio;
use Filament\Resources\Pages\CreateRecord;

class CreateSitioFaq extends CreateRecord
{
    protected static string $resource = SitioFaqResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SitioPropio::conEmpresa($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
