<?php

namespace App\Filament\App\Resources\EmpresaUserResource\Pages;

use App\Filament\App\Resources\EmpresaUserResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEmpresaUser extends CreateRecord
{
    protected static string $resource = EmpresaUserResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Extrae el rol antes de crear, asigna empresa_id y luego sincroniza el rol.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $role = $data['role'] ?? null;
        unset($data['role']);

        $data['empresa_id'] = Filament::getTenant()->id;

        $record = static::getModel()::create($data);

        if ($role) {
            $record->syncRoles([$role]);
        }

        return $record;
    }
}
