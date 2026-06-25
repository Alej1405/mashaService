<?php

namespace App\Filament\Cms\Resources\CmsProductResource\Pages;

use App\Filament\Cms\Resources\CmsProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCmsProducts extends ListRecords
{
    protected static string $resource = CmsProductResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
