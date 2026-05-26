<?php

namespace App\Filament\App\Resources\CustomerResource\Pages;

use App\Filament\App\Resources\CustomerResource;
use App\Mail\WelcomeCustomerMail;
use App\Models\Empresa;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function afterCreate(): void
    {
        $customer = $this->record;

        if (! $customer->email || str_contains($customer->email, '@erp.local')) {
            return;
        }

        if (! filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $empresa = Filament::getTenant();
        if (! $empresa instanceof Empresa) {
            $empresa = Empresa::find($customer->empresa_id);
        }
        if (! $empresa) {
            return;
        }

        try {
            $mail = new WelcomeCustomerMail($customer, $empresa);

            Resend::emails()->send([
                'from'    => config('mail.from.name') . ' <' . config('mail.from.address') . '>',
                'to'      => [$customer->email],
                'subject' => $mail->envelope()->subject,
                'html'    => $mail->buildHtml(),
            ]);
        } catch (\Throwable $e) {
            Log::error('CreateCustomer: no se pudo enviar bienvenida', [
                'customer_id' => $customer->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
