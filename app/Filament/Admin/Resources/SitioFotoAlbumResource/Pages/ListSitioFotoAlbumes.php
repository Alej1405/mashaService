<?php

namespace App\Filament\Admin\Resources\SitioFotoAlbumResource\Pages;

use App\Filament\Admin\Resources\SitioFotoAlbumResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSitioFotoAlbumes extends ListRecords
{
    protected static string $resource = SitioFotoAlbumResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
