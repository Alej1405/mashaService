<?php

namespace App\Filament\Admin\Resources\SitioNosotrosResource\Pages;

use App\Filament\Admin\Resources\SitioNosotrosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSitioNosotros extends ListRecords
{
    protected static string $resource = SitioNosotrosResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
