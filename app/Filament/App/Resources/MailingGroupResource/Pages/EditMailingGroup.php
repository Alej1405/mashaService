<?php

namespace App\Filament\App\Resources\MailingGroupResource\Pages;

use App\Filament\App\Resources\MailingGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMailingGroup extends EditRecord
{
    protected static string $resource = MailingGroupResource::class;

    public function getTitle(): string
    {
        return 'Renombrar: ' . $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver')
                ->label('Ver contactos')
                ->icon('heroicon-o-arrow-left')
                ->url(fn () => MailingGroupResource::getUrl('view', ['record' => $this->record])),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return MailingGroupResource::getUrl('view', ['record' => $this->record]);
    }
}
