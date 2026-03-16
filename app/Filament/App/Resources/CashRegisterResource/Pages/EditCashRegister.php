<?php

namespace App\Filament\App\Resources\CashRegisterResource\Pages;

use App\Filament\App\Resources\CashRegisterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCashRegister extends EditRecord
{
    protected static string $resource = CashRegisterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
