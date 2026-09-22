<?php

namespace App\Filament\Operaciones\Resources\MeasurementUnitResource\Pages;

use App\Filament\Operaciones\Resources\MeasurementUnitResource;
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
