<?php

namespace App\Filament\App\Resources\LogisticsPaymentClaimResource\Pages;

use App\Filament\App\Resources\LogisticsPaymentClaimResource;
use Filament\Resources\Pages\ListRecords;

class ListLogisticsPaymentClaims extends ListRecords
{
    protected static string $resource = LogisticsPaymentClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
