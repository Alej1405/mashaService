<?php

namespace App\Observers;

use App\Models\SupportTicketMensaje;
use App\Modules\Soporte\Actions\NotificarSoporte;

/** Avisa por Telegram (vía n8n) cada mensaje nuevo del hilo de un ticket. */
class SupportTicketMensajeObserver
{
    public function created(SupportTicketMensaje $mensaje): void
    {
        app(NotificarSoporte::class)->mensaje($mensaje);
    }
}
