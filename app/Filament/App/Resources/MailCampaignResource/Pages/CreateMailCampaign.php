<?php

namespace App\Filament\App\Resources\MailCampaignResource\Pages;

use App\Filament\App\Resources\MailCampaignResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMailCampaign extends CreateRecord
{
    protected static string $resource = MailCampaignResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
