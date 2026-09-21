<?php

namespace App\Filament\Admin\Resources\SitioContactoResource\Pages;

use App\Filament\Admin\Resources\SitioContactoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSitioContacto extends ListRecords
{
    protected static string $resource = SitioContactoResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
