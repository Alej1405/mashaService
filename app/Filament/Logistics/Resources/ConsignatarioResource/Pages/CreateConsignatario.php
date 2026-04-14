<?php

namespace App\Filament\Logistics\Resources\ConsignatarioResource\Pages;

use App\Filament\Logistics\Resources\ConsignatarioResource;
use Filament\Resources\Pages\CreateRecord;

class CreateConsignatario extends CreateRecord
{
    protected static string $resource = ConsignatarioResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['empresa_id'] = \Filament\Facades\Filament::getTenant()->id;
        return $data;
    }
}
