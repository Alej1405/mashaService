<?php

namespace App\Filament\Admin\Resources\ServicePlanResource\Pages;

use App\Filament\Admin\Resources\ServicePlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServicePlan extends CreateRecord
{
    protected static string $resource = ServicePlanResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Plan creado correctamente';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Asegurar sort_order al final de la lista
        $data['sort_order'] = \App\Models\ServicePlan::max('sort_order') + 1;

        // modules_template: los valores vienen como string del form, castear a bool
        if (isset($data['modules_template'])) {
            $data['modules_template'] = array_map(
                fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN),
                $data['modules_template']
            );
        }

        return $data;
    }
}
