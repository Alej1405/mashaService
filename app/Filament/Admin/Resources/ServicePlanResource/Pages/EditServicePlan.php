<?php

namespace App\Filament\Admin\Resources\ServicePlanResource\Pages;

use App\Filament\Admin\Resources\ServicePlanResource;
use Filament\Resources\Pages\EditRecord;

class EditServicePlan extends EditRecord
{
    use \App\Filament\Concerns\RegresaAlListado;

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
