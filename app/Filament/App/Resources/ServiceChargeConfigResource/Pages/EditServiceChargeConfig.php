<?php

namespace App\Filament\App\Resources\ServiceChargeConfigResource\Pages;

use App\Filament\App\Resources\ServiceChargeConfigResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServiceChargeConfig extends EditRecord
{
    protected static string $resource = ServiceChargeConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
