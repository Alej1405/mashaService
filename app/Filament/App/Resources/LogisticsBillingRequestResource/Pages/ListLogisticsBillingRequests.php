<?php

namespace App\Filament\App\Resources\LogisticsBillingRequestResource\Pages;

use App\Filament\App\Resources\LogisticsBillingRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListLogisticsBillingRequests extends ListRecords
{
    protected static string $resource = LogisticsBillingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
