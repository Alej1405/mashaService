<?php

namespace App\Filament\App\Resources\MailingContactResource\Pages;

use App\Filament\App\Resources\MailingContactResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMailingContact extends CreateRecord
{
    protected static string $resource = MailingContactResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
