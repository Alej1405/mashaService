<?php

namespace App\Filament\App\Resources\MailingGroupResource\Pages;

use App\Filament\App\Resources\MailingGroupResource;
use Filament\Resources\Pages\ListRecords;

class ListMailingGroups extends ListRecords
{
    protected static string $resource = MailingGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
