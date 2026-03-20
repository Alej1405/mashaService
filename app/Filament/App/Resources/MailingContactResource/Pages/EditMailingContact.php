<?php

namespace App\Filament\App\Resources\MailingContactResource\Pages;

use App\Filament\App\Resources\MailingContactResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMailingContact extends EditRecord
{
    protected static string $resource = MailingContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Eliminar'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
