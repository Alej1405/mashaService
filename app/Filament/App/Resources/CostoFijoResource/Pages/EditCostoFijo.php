<?php

namespace App\Filament\App\Resources\CostoFijoResource\Pages;

use App\Filament\App\Resources\CostoFijoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCostoFijo extends EditRecord
{
    protected static string $resource = CostoFijoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
