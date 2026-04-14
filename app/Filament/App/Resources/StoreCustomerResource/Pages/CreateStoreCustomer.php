<?php

namespace App\Filament\App\Resources\StoreCustomerResource\Pages;

use App\Filament\App\Resources\StoreCustomerResource;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateStoreCustomer extends CreateRecord
{
    protected static string $resource = StoreCustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['empresa_id'] = Filament::getTenant()->id;

        // Si no viene contraseña del form, usar cedula_ruc como contraseña inicial
        if (empty($data['password']) && ! empty($data['cedula_ruc'])) {
            $data['password'] = Hash::make($data['cedula_ruc']);
        }

        return $data;
    }
}
