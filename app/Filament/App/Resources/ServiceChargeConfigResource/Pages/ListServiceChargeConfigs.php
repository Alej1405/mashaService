<?php

namespace App\Filament\App\Resources\ServiceChargeConfigResource\Pages;

use App\Filament\App\Resources\ServiceChargeConfigResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceChargeConfigs extends ListRecords
{
    protected static string $resource = ServiceChargeConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
