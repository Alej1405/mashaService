<?php

namespace App\Filament\App\Resources\CreditCardResource\Pages;

use App\Filament\App\Resources\CreditCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCreditCard extends EditRecord
{
    protected static string $resource = CreditCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
