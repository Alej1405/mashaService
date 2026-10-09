<?php

namespace App\Filament\Admin\Resources\SitioFotoCategoriaResource\Pages;

use App\Filament\Admin\Resources\SitioFotoCategoriaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSitioFotoCategorias extends ListRecords
{
    protected static string $resource = SitioFotoCategoriaResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
