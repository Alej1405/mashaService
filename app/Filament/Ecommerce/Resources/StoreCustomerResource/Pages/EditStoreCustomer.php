<?php

namespace App\Filament\Ecommerce\Resources\StoreCustomerResource\Pages;

use App\Filament\Ecommerce\Resources\StoreCustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStoreCustomer extends EditRecord
{
    protected static string $resource = StoreCustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
