<?php

namespace App\Observers;

use App\Mail\WelcomeCustomerMail;
use App\Models\Customer;
use App\Models\Empresa;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;

/**
 * Observer unificado para Customer.
 * Al crear un cliente con email válido envía el correo de bienvenida.
 */
class CustomerPortalObserver
{
    public function created(Customer $customer): void
    {
        $this->enviarBienvenida($customer);
    }

    private function enviarBienvenida(Customer $customer): void
    {
        if (! $customer->email) {
            return;
        }

        if (str_contains($customer->email, '@erp.local')) {
            return;
        }

        if (! filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $empresa = Empresa::find($customer->empresa_id);
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
            Log::error('CustomerPortalObserver: error al enviar bienvenida', [
                'customer_id' => $customer->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
