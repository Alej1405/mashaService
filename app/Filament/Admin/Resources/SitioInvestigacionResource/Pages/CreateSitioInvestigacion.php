<?php

namespace App\Filament\Admin\Resources\SitioInvestigacionResource\Pages;

use App\Filament\Admin\Resources\SitioInvestigacionResource;
use App\Support\SitioPropio;
use Filament\Resources\Pages\CreateRecord;

class CreateSitioInvestigacion extends CreateRecord
{
    protected static string $resource = SitioInvestigacionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SitioPropio::conEmpresa($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
