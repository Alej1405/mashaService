<?php

namespace App\Filament\Logistics\Resources\PackageResource\Pages;

use App\Filament\Logistics\Resources\PackageResource;
use App\Mail\LogisticsPackageStatusMail;
use App\Models\Empresa;
use App\Models\StoreCustomer;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditPackage extends EditRecord
{
    protected static string $resource = PackageResource::class;

    /** Estado anterior al guardar, para detectar cambio. */
    private ?string $estadoAnterior = null;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Guardar estado actual antes de que el usuario lo cambie
        $this->estadoAnterior = $this->record->estado;
        return $data;
    }

    protected function afterSave(): void
    {
        $package = $this->record->fresh();

        // Notificar solo si cambió el estado o si acaba de asignarse cliente
        $estadoCambio    = $package->estado !== $this->estadoAnterior;
        $clientePresente = (bool) $package->store_customer_id;

        if ($estadoCambio && $clientePresente) {
            $this->notificarCliente($package);
        }
    }

    private function notificarCliente(\App\Models\LogisticsPackage $package): void
    {
        $customer = StoreCustomer::find($package->store_customer_id);
        $empresa  = Empresa::find($package->empresa_id);

        if (! $customer || ! $empresa || ! filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        Mail::mailer('resend')
            ->to($customer->email, $customer->nombre_completo)
            ->send(new LogisticsPackageStatusMail($package, $customer, $empresa));
    }
}
