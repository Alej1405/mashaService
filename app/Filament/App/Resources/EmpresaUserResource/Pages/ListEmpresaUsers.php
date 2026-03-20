<?php

namespace App\Filament\App\Resources\EmpresaUserResource\Pages;

use App\Filament\App\Resources\EmpresaUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmpresaUsers extends ListRecords
{
    protected static string $resource = EmpresaUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nuevo usuario'),
        ];
    }
}
