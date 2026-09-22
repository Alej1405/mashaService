<?php

namespace App\Filament\Operaciones\Resources\AlmacenResource\Pages;

use App\Filament\Operaciones\Resources\AlmacenResource;

use Filament\Resources\Pages\CreateRecord;

class CreateAlmacen extends CreateRecord
{
    protected static string $resource = AlmacenResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
