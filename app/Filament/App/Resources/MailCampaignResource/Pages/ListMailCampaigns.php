<?php

namespace App\Filament\App\Resources\MailCampaignResource\Pages;

use App\Filament\App\Resources\MailCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMailCampaigns extends ListRecords
{
    protected static string $resource = MailCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nueva campaña'),
        ];
    }
}
