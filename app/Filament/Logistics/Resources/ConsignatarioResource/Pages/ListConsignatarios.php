<?php

namespace App\Filament\Logistics\Resources\ConsignatarioResource\Pages;

use App\Filament\Logistics\Resources\ConsignatarioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListConsignatarios extends ListRecords
{
    protected static string $resource = ConsignatarioResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
