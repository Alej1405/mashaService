<?php

namespace App\Filament\Admin\Resources\SitioServicioResource\Pages;

use App\Filament\Admin\Resources\SitioServicioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSitioServicio extends ListRecords
{
    protected static string $resource = SitioServicioResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
