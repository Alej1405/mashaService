<?php

namespace App\Filament\Resources\ServicePlanResource\Pages;

use App\Filament\Resources\ServicePlanResource;
use Filament\Resources\Pages\ListRecords;

class ListServicePlans extends ListRecords
{
    protected static string $resource = ServicePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
