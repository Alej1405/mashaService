<?php

namespace App\Filament\Ecommerce\Resources\StoreOrderResource\Pages;

use App\Filament\Ecommerce\Resources\StoreOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStoreOrders extends ListRecords
{
    protected static string $resource = StoreOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
