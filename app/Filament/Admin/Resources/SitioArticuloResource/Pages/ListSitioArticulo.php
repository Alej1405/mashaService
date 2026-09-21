<?php

namespace App\Filament\Admin\Resources\SitioArticuloResource\Pages;

use App\Filament\Admin\Resources\SitioArticuloResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSitioArticulo extends ListRecords
{
    protected static string $resource = SitioArticuloResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
