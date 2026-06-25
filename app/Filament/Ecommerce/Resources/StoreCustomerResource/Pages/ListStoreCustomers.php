<?php

namespace App\Filament\Ecommerce\Resources\StoreCustomerResource\Pages;

use App\Filament\Ecommerce\Resources\StoreCustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStoreCustomers extends ListRecords
{
    protected static string $resource = StoreCustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
