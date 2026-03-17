<?php

namespace App\Filament\Resources\EmpresaMailingResource\Pages;

use App\Filament\Resources\EmpresaMailingResource;
use Filament\Resources\Pages\ListRecords;

class ListEmpresaMailing extends ListRecords
{
    protected static string $resource = EmpresaMailingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
