<?php

namespace App\Filament\Cms\Resources\CmsTerminosResource\Pages;

use App\Filament\Cms\Resources\CmsTerminosResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCmsTerminos extends ListRecords
{
    protected static string $resource = CmsTerminosResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
