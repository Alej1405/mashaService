<?php

namespace App\Filament\App\Resources\MeasurementUnitResource\Pages;

use App\Filament\App\Resources\MeasurementUnitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMeasurementUnit extends EditRecord
{
    protected static string $resource = MeasurementUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
