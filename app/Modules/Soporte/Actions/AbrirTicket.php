<?php

namespace App\Modules\Soporte\Actions;

use App\Models\SupportTicket;
use App\Models\SupportTicketMensaje;
use App\Models\User;
use App\Shared\Attributes\Documentado;
use Illuminate\Http\UploadedFile;

/**
 * Abre un ticket de soporte en una empresa y mete sus adjuntos al hilo.
 * La usan el panel y la API de n8n (Telegram).
 */
#[Documentado(
    grupo: 'Soporte',
    descripcion: 'Crea un ticket de soporte con sus adjuntos opcionales.',
    tipo: 'action',
)]
final class AbrirTicket
{
    public function __construct(private readonly RegistrarMensajeTicket $registrar) {}

    /** @param array<int,UploadedFile> $adjuntos */
    public function handle(User $user, int $empresaId, string $asunto, string $descripcion, string $prioridad = 'media', string $canal = SupportTicketMensaje::PANEL, array $adjuntos = []): SupportTicket
    {
        $ticket = new SupportTicket;
        $ticket->forceFill([
            'empresa_id'  => $empresaId,
            'user_id'     => $user->id,
            'asunto'      => $asunto,
            'descripcion' => $descripcion,
            'prioridad'   => $prioridad,
            'status'      => SupportTicket::ABIERTO,
        ])->save();

        // Cada archivo entra al hilo como un mensaje con su adjunto.
        foreach ($adjuntos as $archivo) {
            $this->registrar->handle($ticket, $user, $canal, null, $archivo);
        }

        return $ticket;
    }
}
