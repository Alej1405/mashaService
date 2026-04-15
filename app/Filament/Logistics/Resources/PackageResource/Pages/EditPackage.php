<?php

namespace App\Filament\Logistics\Resources\PackageResource\Pages;

use App\Filament\Logistics\Resources\PackageResource;
use App\Mail\LogisticsPackageStatusMail;
use App\Models\Empresa;
use App\Models\StoreCustomer;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Resend\Laravel\Facades\Resend;

class EditPackage extends EditRecord
{
    protected static string $resource = PackageResource::class;

    private ?string $estadoAnterior           = null;
    private ?string $estadoSecundarioAnterior = null;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->estadoAnterior           = $this->record->estado;
        $this->estadoSecundarioAnterior = $this->record->estado_secundario;
        return $data;
    }

    protected function afterSave(): void
    {
        $package = $this->record->fresh();

        $estadoCambio     = $package->estado !== $this->estadoAnterior;
        $secundarioCambio = $package->estado_secundario !== $this->estadoSecundarioAnterior;

        if (! ($estadoCambio || $secundarioCambio) || ! $package->store_customer_id) {
            return;
        }

        $solicitarPago = $estadoCambio && $package->estado === 'finalizado_aduana';
        $this->notificarCliente($package, $solicitarPago);
    }

    private function notificarCliente(\App\Models\LogisticsPackage $package, bool $solicitarPago = false): void
    {
        $customer = StoreCustomer::find($package->store_customer_id);
        $empresa  = Empresa::find($package->empresa_id);

        if (! $customer || ! $empresa || ! filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $mail = new LogisticsPackageStatusMail($package, $customer, $empresa, $solicitarPago);

        try {
            Resend::emails()->send([
                'from'    => config('mail.from.name') . ' <' . config('mail.from.address') . '>',
                'to'      => [$customer->email],
                'subject' => $mail->envelope()->subject,
                'html'    => $mail->buildHtml(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error enviando notificación logística', [
                'package_id' => $package->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
