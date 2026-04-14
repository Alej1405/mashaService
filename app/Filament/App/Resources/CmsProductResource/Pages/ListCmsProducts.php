<?php

namespace App\Filament\App\Resources\CmsProductResource\Pages;

use App\Filament\App\Resources\CmsProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCmsProducts extends ListRecords
{
    protected static string $resource = CmsProductResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
