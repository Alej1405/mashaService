<?php

namespace App\Filament\Admin\Resources\SitioSeoResource\Pages;

use App\Filament\Admin\Resources\SitioSeoResource;
use App\Support\SitioPropio;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSitioSeo extends ManageRecords
{
    protected static string $resource = SitioSeoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(fn (array $data): array => SitioPropio::conEmpresa($data)),
        ];
    }
}
