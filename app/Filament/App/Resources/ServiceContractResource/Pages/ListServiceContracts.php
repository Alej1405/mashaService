<?php

namespace App\Filament\App\Resources\ServiceContractResource\Pages;

use App\Filament\App\Resources\ServiceContractResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceContracts extends ListRecords
{
    protected static string $resource = ServiceContractResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
