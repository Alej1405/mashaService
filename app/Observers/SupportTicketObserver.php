<?php

namespace App\Observers;

use App\Models\SupportTicket;
use App\Modules\Soporte\Actions\NotificarSoporte;

/** Avisa por Telegram (vía n8n) cuando entra un ticket o cambia su estado. */
class SupportTicketObserver
{
    public function created(SupportTicket $ticket): void
    {
        app(NotificarSoporte::class)->ticketNuevo($ticket);
    }

    public function updated(SupportTicket $ticket): void
    {
        if ($ticket->wasChanged('status')) {
            app(NotificarSoporte::class)->estado($ticket);
        }
    }
}
