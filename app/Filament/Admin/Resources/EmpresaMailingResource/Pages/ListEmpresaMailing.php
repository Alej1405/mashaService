<?php

namespace App\Filament\Admin\Resources\EmpresaMailingResource\Pages;

use App\Filament\Admin\Resources\EmpresaMailingResource;
use Filament\Resources\Pages\ListRecords;

class ListEmpresaMailing extends ListRecords
{
    protected static string $resource = EmpresaMailingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
