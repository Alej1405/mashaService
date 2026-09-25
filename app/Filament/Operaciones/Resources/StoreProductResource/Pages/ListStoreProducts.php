<?php

namespace App\Filament\Operaciones\Resources\StoreProductResource\Pages;

use App\Filament\Operaciones\Resources\StoreProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStoreProducts extends ListRecords
{
    protected static string $resource = StoreProductResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
