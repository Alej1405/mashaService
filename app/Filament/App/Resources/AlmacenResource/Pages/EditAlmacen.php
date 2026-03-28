<?php

namespace App\Filament\App\Resources\AlmacenResource\Pages;

use App\Filament\App\Resources\AlmacenResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAlmacen extends EditRecord
{
    protected static string $resource = AlmacenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
