<?php

namespace App\Filament\App\Resources\JournalEntryResource\Pages;

use App\Filament\App\Resources\JournalEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJournalEntry extends EditRecord
{
    use \App\Filament\Concerns\RegresaAlListado;

    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
