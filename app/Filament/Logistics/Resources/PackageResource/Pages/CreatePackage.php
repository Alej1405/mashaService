<?php

namespace App\Filament\Logistics\Resources\PackageResource\Pages;

use App\Filament\Logistics\Resources\PackageResource;
use App\Mail\LogisticsPackageStatusMail;
use App\Models\Empresa;
use App\Models\StoreCustomer;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Resend\Laravel\Facades\Resend;

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

        $mail = new LogisticsPackageStatusMail($package, $customer, $empresa);

        Resend::emails()->send([
            'from'    => config('mail.from.name') . ' <' . config('mail.from.address') . '>',
            'to'      => [$customer->email],
            'subject' => $mail->envelope()->subject,
            'html'    => $mail->buildHtml(),
        ]);
    }
}
