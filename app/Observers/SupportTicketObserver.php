<?php

namespace App\Observers;

use App\Models\SupportTicket;
use App\Modules\Soporte\Actions\NotificarSoporte;
use Illuminate\Support\Facades\Storage;

/** Avisa cuando entra un ticket o cambia su estado, y limpia sus adjuntos al borrarlo. */
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

    /** Al borrar el ticket se van también sus adjuntos (el hilo cae por cascada). */
    public function deleted(SupportTicket $ticket): void
    {
        Storage::disk('public')->deleteDirectory('soporte/tickets/'.$ticket->id);
    }
}
