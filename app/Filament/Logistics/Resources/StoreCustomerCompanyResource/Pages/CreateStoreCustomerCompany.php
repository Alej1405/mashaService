<?php

namespace App\Filament\Logistics\Resources\StoreCustomerCompanyResource\Pages;

use App\Filament\Logistics\Resources\StoreCustomerCompanyResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateStoreCustomerCompany extends CreateRecord
{
    protected static string $resource = StoreCustomerCompanyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['empresa_id'] = Filament::getTenant()->id;
        return $data;
    }
}
