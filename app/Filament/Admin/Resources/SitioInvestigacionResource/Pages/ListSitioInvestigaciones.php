<?php

namespace App\Filament\Admin\Resources\SitioInvestigacionResource\Pages;

use App\Filament\Admin\Resources\SitioInvestigacionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSitioInvestigaciones extends ListRecords
{
    protected static string $resource = SitioInvestigacionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
