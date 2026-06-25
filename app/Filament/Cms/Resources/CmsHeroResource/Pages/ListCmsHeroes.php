<?php

namespace App\Filament\Cms\Resources\CmsHeroResource\Pages;

use App\Filament\Cms\Resources\CmsHeroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCmsHeroes extends ListRecords
{
    protected static string $resource = CmsHeroResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
