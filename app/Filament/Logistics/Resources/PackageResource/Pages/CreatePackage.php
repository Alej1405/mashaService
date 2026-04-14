<?php

namespace App\Filament\Logistics\Resources\PackageResource\Pages;

use App\Filament\Logistics\Resources\PackageResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePackage extends CreateRecord
{
    protected static string $resource = PackageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['empresa_id'] = \Filament\Facades\Filament::getTenant()->id;
        $data['estado']     = 'registrado';
        return $data;
    }
}
