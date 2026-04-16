<?php

namespace App\Observers;

use App\Mail\WelcomeCustomerMail;
use App\Models\Customer;
use App\Models\Empresa;
use App\Models\StoreCustomer;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;

/**
 * Al crear un StoreCustomer, busca o crea el Customer ERP equivalente
 * para que esté disponible en la contabilidad y facturación.
 */
class StoreCustomerObserver
{
    public function created(StoreCustomer $storeCustomer): void
    {
        if (! $storeCustomer->customer_id) {
            try {
                $this->vincularOCrearCustomerErp($storeCustomer);
            } catch (\Throwable $e) {
                Log::warning('StoreCustomerObserver: no se pudo crear Customer ERP', [
                    'store_customer_id' => $storeCustomer->id,
                    'error'             => $e->getMessage(),
                ]);
            }
        }

        $this->enviarBienvenida($storeCustomer);
    }

    public function updated(StoreCustomer $storeCustomer): void
    {
        if ($storeCustomer->customer_id) {
            return;
        }

        // Si acaban de agregar cedula_ruc, intentar vincular
        if ($storeCustomer->isDirty('cedula_ruc') && $storeCustomer->cedula_ruc) {
            try {
                $this->vincularOCrearCustomerErp($storeCustomer);
            } catch (\Throwable $e) {
                Log::warning('StoreCustomerObserver updated: no se pudo vincular Customer ERP', [
                    'store_customer_id' => $storeCustomer->id,
                    'error'             => $e->getMessage(),
                ]);
            }
        }
    }

    // ── Lógica principal ─────────────────────────────────────────────────────

    private function vincularOCrearCustomerErp(StoreCustomer $sc): void
    {
        $empresaId = $sc->empresa_id;
        $cedula    = $sc->cedula_ruc;

        // 1. Buscar Customer ERP existente con la misma identificación
        $customer = null;
        if ($cedula) {
            $customer = Customer::where('empresa_id', $empresaId)
                ->where('numero_identificacion', $cedula)
                ->first();
        }

        // 2. Si no existe, crear uno nuevo
        if (! $customer) {
            $tipoId = match ($sc->tipo) {
                'empresa' => strlen(preg_replace('/\D/', '', $cedula ?? '')) === 13 ? 'ruc' : 'ruc',
                default   => strlen(preg_replace('/\D/', '', $cedula ?? '')) === 10 ? 'cedula' : 'pasaporte',
            };

            $customer = Customer::create([
                'empresa_id'            => $empresaId,
                'nombre'                => $sc->nombre_completo,
                'tipo_persona'          => $sc->tipo === 'empresa' ? 'juridica' : 'natural',
                'tipo_identificacion'   => $cedula ? $tipoId : 'consumidor_final',
                'numero_identificacion' => $cedula ?? '9999999999999',
                'email'                 => $sc->email,
                'telefono'              => $sc->telefono,
                'activo'                => true,
            ]);
        }

        // 3. Vincular
        $sc->updateQuietly(['customer_id' => $customer->id]);
    }

    // ── Bienvenida ───────────────────────────────────────────────────────────

    private function enviarBienvenida(StoreCustomer $storeCustomer): void
    {
        // No enviar a correos falsos generados automáticamente
        if (str_contains($storeCustomer->email, '@erp.local')) {
            return;
        }

        if (! filter_var($storeCustomer->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $empresa = Empresa::find($storeCustomer->empresa_id);
        if (! $empresa) {
            return;
        }

        try {
            $mail = new WelcomeCustomerMail($storeCustomer, $empresa);

            Resend::emails()->send([
                'from'    => config('mail.from.name') . ' <' . config('mail.from.address') . '>',
                'to'      => [$storeCustomer->email],
                'subject' => $mail->envelope()->subject,
                'html'    => $mail->buildHtml(),
            ]);
        } catch (\Throwable $e) {
            Log::error('StoreCustomerObserver: no se pudo enviar bienvenida', [
                'store_customer_id' => $storeCustomer->id,
                'error'             => $e->getMessage(),
            ]);
        }
    }
}
