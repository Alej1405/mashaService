<?php

namespace App\Filament\Ecommerce\Resources\StoreCategoryResource\Pages;

use App\Filament\Ecommerce\Resources\StoreCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStoreCategories extends ListRecords
{
    protected static string $resource = StoreCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
