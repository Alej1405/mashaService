<?php

namespace App\Filament\Logistics\Resources\StoreCustomerCompanyResource\Pages;

use App\Filament\Logistics\Resources\StoreCustomerCompanyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStoreCustomerCompanies extends ListRecords
{
    protected static string $resource = StoreCustomerCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
