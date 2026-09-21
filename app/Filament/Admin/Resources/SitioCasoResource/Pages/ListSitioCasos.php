<?php

namespace App\Filament\Admin\Resources\SitioCasoResource\Pages;

use App\Filament\Admin\Resources\SitioCasoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSitioCasos extends ListRecords
{
    protected static string $resource = SitioCasoResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
