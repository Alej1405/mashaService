<?php

namespace App\Filament\App\Resources\CashSessionResource\Pages;

use App\Filament\App\Resources\CashSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCashSession extends EditRecord
{
    use \App\Filament\Concerns\RegresaAlListado;

    protected static string $resource = CashSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
