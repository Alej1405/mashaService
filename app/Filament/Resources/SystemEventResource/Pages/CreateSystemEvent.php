<?php

namespace App\Filament\Resources\SystemEventResource\Pages;

use App\Filament\Resources\SystemEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSystemEvent extends CreateRecord
{
    protected static string $resource = SystemEventResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
