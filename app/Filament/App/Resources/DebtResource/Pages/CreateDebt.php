<?php

namespace App\Filament\App\Resources\DebtResource\Pages;

use App\Filament\App\Resources\DebtResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDebt extends CreateRecord
{
    protected static string $resource = DebtResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
