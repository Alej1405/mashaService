<?php

namespace App\Filament\Admin\Resources\SitioPlanResource\Pages;

use App\Filament\Admin\Resources\SitioPlanResource;
use App\Support\SitioPropio;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSitioPlanes extends ManageRecords
{
    protected static string $resource = SitioPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(fn (array $data): array => SitioPropio::conEmpresa($data)),
        ];
    }
}
