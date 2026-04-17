<?php

namespace App\Filament\Logistics\Resources\StoreCustomerCompanyResource\Pages;

use App\Filament\Logistics\Resources\StoreCustomerCompanyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStoreCustomerCompany extends EditRecord
{
    protected static string $resource = StoreCustomerCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
