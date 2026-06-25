<?php

namespace App\Filament\Cms\Resources\CmsServiceResource\Pages;

use App\Filament\Cms\Resources\CmsServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCmsServices extends ListRecords
{
    protected static string $resource = CmsServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
