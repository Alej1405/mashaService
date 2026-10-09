<?php

namespace App\Filament\Admin\Resources\SitioFotoAlbumResource\Pages;

use App\Filament\Admin\Resources\SitioFotoAlbumResource;
use App\Support\SitioPropio;
use Filament\Resources\Pages\CreateRecord;

class CreateSitioFotoAlbum extends CreateRecord
{
    protected static string $resource = SitioFotoAlbumResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SitioPropio::conEmpresa($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
