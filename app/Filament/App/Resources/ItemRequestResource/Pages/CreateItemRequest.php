<?php

namespace App\Filament\App\Resources\ItemRequestResource\Pages;

use App\Filament\App\Resources\ItemRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateItemRequest extends CreateRecord
{
    protected static string $resource = ItemRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by'] = auth()->id();
        return $data;
    }
}
