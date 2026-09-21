<?php

namespace App\Filament\Admin\Resources\SitioLogoResource\Pages;

use App\Filament\Admin\Resources\SitioLogoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSitioLogo extends ListRecords
{
    protected static string $resource = SitioLogoResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
