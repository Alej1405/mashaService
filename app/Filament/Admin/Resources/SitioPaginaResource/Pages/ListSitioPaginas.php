<?php

namespace App\Filament\Admin\Resources\SitioPaginaResource\Pages;

use App\Filament\Admin\Resources\SitioPaginaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSitioPaginas extends ListRecords
{
    protected static string $resource = SitioPaginaResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
