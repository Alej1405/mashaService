<?php

namespace App\Filament\Resources\ServicePlanResource\Pages;

use App\Filament\Resources\ServicePlanResource;
use Filament\Resources\Pages\EditRecord;

class EditServicePlan extends EditRecord
{
    protected static string $resource = ServicePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Plan actualizado correctamente';
    }
}
