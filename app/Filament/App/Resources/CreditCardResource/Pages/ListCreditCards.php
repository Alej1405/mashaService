<?php

namespace App\Filament\App\Resources\CreditCardResource\Pages;

use App\Filament\App\Resources\CreditCardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCreditCards extends ListRecords
{
    protected static string $resource = CreditCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
