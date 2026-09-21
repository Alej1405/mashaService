<?php

namespace App\Filament\Admin\Resources\SitioNavegacionResource\Pages;

use App\Filament\Admin\Resources\SitioNavegacionResource;
use App\Support\SitioPropio;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSitioNavegacion extends ManageRecords
{
    protected static string $resource = SitioNavegacionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(fn (array $data): array => SitioPropio::conEmpresa($data)),
        ];
    }
}
