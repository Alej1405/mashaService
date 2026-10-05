<?php

namespace App\Modules\Soporte\Actions;

use App\Models\SupportTicket;
use App\Models\SupportTicketMensaje;
use App\Models\User;
use App\Shared\Attributes\Documentado;
use Illuminate\Http\UploadedFile;

/**
 * Agrega un mensaje al hilo de un ticket, con su adjunto si lo trae.
 * El remitente sale del rol: super_admin responde como soporte; el resto, como empresa.
 * La usan el panel y la API de n8n (Telegram).
 */
#[Documentado(
    grupo: 'Soporte',
    descripcion: 'Registra un mensaje (texto y/o adjunto) en el hilo de un ticket de soporte.',
    tipo: 'action',
)]
final class RegistrarMensajeTicket
{
    public function handle(SupportTicket $ticket, User $user, string $canal, ?string $mensaje, ?UploadedFile $archivo = null): SupportTicketMensaje
    {
        $adjunto = [];
        if ($archivo) {
            $adjunto = [
                'adjunto_path'   => $archivo->store('soporte/tickets/'.$ticket->id, 'public'),
                'adjunto_nombre' => $archivo->getClientOriginalName(),
                'adjunto_mime'   => $archivo->getMimeType(),
            ];
        }

        $nuevo = SupportTicketMensaje::create([
            'support_ticket_id' => $ticket->id,
            'user_id'           => $user->id,
            'remitente'         => self::remitente($user),
            'canal'             => $canal,
            'mensaje'           => filled($mensaje) ? trim($mensaje) : null,
        ] + $adjunto);

        // Si responde soporte, el ticket pasa a "en proceso".
        if ($nuevo->remitente === 'soporte' && $ticket->status === 'abierto') {
            $ticket->update(['status' => 'en_proceso']);
        }

        return $nuevo;
    }

    public static function remitente(User $user): string
    {
        return $user->hasRole('super_admin') ? 'soporte' : 'empresa';
    }
}
