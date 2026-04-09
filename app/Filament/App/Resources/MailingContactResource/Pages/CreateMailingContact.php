<?php

namespace App\Filament\App\Resources\MailingContactResource\Pages;

use App\Filament\App\Resources\MailingContactResource;
use App\Models\MailingGroup;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateMailingContact extends CreateRecord
{
    protected static string $resource = MailingContactResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['mailing_group_id'] = MailingGroup::assignGroup(Filament::getTenant()->id);
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
