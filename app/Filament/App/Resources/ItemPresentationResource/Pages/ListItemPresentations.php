<?php

namespace App\Filament\App\Resources\ItemPresentationResource\Pages;

use App\Filament\App\Resources\ItemPresentationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListItemPresentations extends ListRecords
{
    protected static string $resource = ItemPresentationResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
