<?php

namespace App\Filament\App\Resources\JournalEntryResource\Pages;

use App\Filament\App\Resources\JournalEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateJournalEntry extends CreateRecord
{
    use \App\Filament\Concerns\RegresaAlListado;

    protected static string $resource = JournalEntryResource::class;
}
