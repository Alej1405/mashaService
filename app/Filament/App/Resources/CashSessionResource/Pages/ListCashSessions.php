<?php

namespace App\Filament\App\Resources\CashSessionResource\Pages;

use App\Filament\App\Resources\CashSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashSessions extends ListRecords
{
    protected static string $resource = CashSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
