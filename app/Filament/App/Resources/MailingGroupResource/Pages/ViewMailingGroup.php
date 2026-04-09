<?php

namespace App\Filament\App\Resources\MailingGroupResource\Pages;

use App\Filament\App\Resources\MailingGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMailingGroup extends ViewRecord
{
    protected static string $resource = MailingGroupResource::class;

    public function getTitle(): string
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Renombrar grupo'),
        ];
    }
}
