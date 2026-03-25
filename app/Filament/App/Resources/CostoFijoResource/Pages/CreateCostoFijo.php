<?php

namespace App\Filament\App\Resources\CostoFijoResource\Pages;

use App\Filament\App\Resources\CostoFijoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCostoFijo extends CreateRecord
{
    protected static string $resource = CostoFijoResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
