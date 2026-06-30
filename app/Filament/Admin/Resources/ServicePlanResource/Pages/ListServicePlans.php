<?php

namespace App\Filament\Admin\Resources\ServicePlanResource\Pages;

use App\Filament\Admin\Resources\ServicePlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServicePlans extends ListRecords
{
    protected static string $resource = ServicePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nuevo plan'),
        ];
    }
}
