<?php

namespace App\Filament\App\Resources\LogisticsShipmentBillResource\Pages;

use App\Filament\App\Resources\LogisticsShipmentBillResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLogisticsShipmentBill extends EditRecord
{
    protected static string $resource = LogisticsShipmentBillResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
