<?php

namespace App\Filament\Logistics\Resources\PackageResource\Pages;

use App\Filament\Logistics\Resources\PackageResource;
use App\Mail\LogisticsPackageStatusMail;
use App\Models\Empresa;
use App\Models\StoreCustomer;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreatePackage extends CreateRecord
{
    protected static string $resource = PackageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['empresa_id'] = Filament::getTenant()->id;
        $data['estado']     = 'registrado';
        return $data;
    }

    protected function afterCreate(): void
    {
        $this->notificarCliente($this->record);
    }

    private function notificarCliente(\App\Models\LogisticsPackage $package): void
    {
        if (! $package->store_customer_id) {
            return;
        }

        $customer = StoreCustomer::find($package->store_customer_id);
        $empresa  = Empresa::find($package->empresa_id);

        if (! $customer || ! $empresa || ! filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        Mail::to($customer->email, $customer->nombre_completo)
            ->send(new LogisticsPackageStatusMail($package, $customer, $empresa));
    }
}
