<?php

namespace App\Filament\Cms\Resources\CmsContactResource\Pages;

use App\Filament\Cms\Resources\CmsContactResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCmsContacts extends ListRecords
{
    protected static string $resource = CmsContactResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
