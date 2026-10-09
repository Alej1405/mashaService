<?php

namespace App\Filament\Admin\Resources\SitioFotoAlbumResource\Pages;

use App\Filament\Admin\Resources\SitioFotoAlbumResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSitioFotoAlbum extends EditRecord
{
    protected static string $resource = SitioFotoAlbumResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
