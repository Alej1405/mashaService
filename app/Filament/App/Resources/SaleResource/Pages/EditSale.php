<?php

namespace App\Filament\App\Resources\SaleResource\Pages;

use App\Filament\App\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSale extends EditRecord
{
    use \App\Filament\Concerns\RegresaAlListado;

    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
