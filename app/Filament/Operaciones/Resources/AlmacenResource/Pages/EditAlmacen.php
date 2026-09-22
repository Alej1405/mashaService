<?php

namespace App\Filament\Operaciones\Resources\AlmacenResource\Pages;

use App\Filament\Operaciones\Resources\AlmacenResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAlmacen extends EditRecord
{
    protected static string $resource = AlmacenResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
