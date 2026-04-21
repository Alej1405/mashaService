<?php

namespace App\Filament\App\Resources\ServiceChargeConfigResource\Pages;

use App\Filament\App\Resources\ServiceChargeConfigResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceChargeConfig extends CreateRecord
{
    protected static string $resource = ServiceChargeConfigResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['empresa_id'] = Filament::getTenant()->id;
        return $data;
    }
}
