<?php

namespace App\Observers;

use App\Mail\NuevoDisenioMail;
use App\Models\Empresa;
use App\Models\ServiceDesign;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;

class ServiceDesignObserver
{
    public function created(ServiceDesign $design): void
    {
        try {
            $empresa = Empresa::find($design->empresa_id);
            if (! $empresa) {
                return;
            }

            $destinatarios = $this->obtenerEmails($empresa);
            if (empty($destinatarios)) {
                return;
            }

            $mail = new NuevoDisenioMail(
                tipoDisenio:    'servicio',
                nombreDisenio:  $design->nombre,
                categoriaDisenio: $design->categoria ?? '',
                empresa:        $empresa,
            );

            Resend::emails()->send([
                'from'    => config('mail.from.name') . ' <' . config('mail.from.address') . '>',
                'to'      => $destinatarios,
                'subject' => $mail->envelope()->subject,
                'html'    => $mail->buildHtml(),
            ]);
        } catch (\Throwable $e) {
            Log::error('ServiceDesignObserver: error al enviar notificación', [
                'design_id' => $design->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    private function obtenerEmails(Empresa $empresa): array
    {
        $emails = collect();

        $emails = $emails->merge(
            $empresa->users()->whereNotNull('email')->pluck('email')
        );

        $emails = $emails->merge(
            $empresa->accessUsers()->whereNotNull('email')->pluck('email')
        );

        return $emails
            ->unique()
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->values()
            ->toArray();
    }
}
