<?php

namespace App\Filament\App\Resources\MailCampaignResource\Pages;

use App\Filament\App\Resources\MailCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMailCampaign extends EditRecord
{
    protected static string $resource = MailCampaignResource::class;

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
