<?php

namespace App\Filament\App\Resources\MailTemplateResource\Pages;

use App\Filament\App\Resources\MailTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMailTemplate extends CreateRecord
{
    protected static string $resource = MailTemplateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Plantilla creada correctamente';
    }
}
