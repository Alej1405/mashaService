<?php

namespace App\Filament\Admin\Resources\SitioHeroResource\Pages;

use App\Filament\Admin\Resources\SitioHeroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSitioHero extends ListRecords
{
    protected static string $resource = SitioHeroResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
