<?php

namespace App\Filament\Operaciones\Resources\AlmacenResource\Pages;

use App\Filament\Operaciones\Resources\AlmacenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAlmacenes extends ListRecords
{
    protected static string $resource = AlmacenResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
