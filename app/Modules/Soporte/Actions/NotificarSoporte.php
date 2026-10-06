<?php

namespace App\Modules\Soporte\Actions;

use App\Models\SupportTicket;
use App\Models\SupportTicketMensaje;
use App\Modules\Soporte\Queries\DestinatariosTelegram;
use App\Shared\Attributes\Documentado;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Avisa a n8n de un evento de soporte; n8n lo reparte por Telegram.
 * Lo que dice la empresa va a soporte; lo que dice soporte va a la empresa.
 * El ERP decide los destinatarios; n8n solo entrega. El envío sale después de
 * responder al usuario: si n8n tarda o está caído, la pantalla no espera y el
 * ticket se guarda igual (solo queda el aviso en el log).
 */
#[Documentado(
    grupo: 'Soporte',
    descripcion: 'Envía a n8n los avisos de tickets nuevos, mensajes y cambios de estado, con sus destinatarios de Telegram.',
    tipo: 'action',
)]
final class NotificarSoporte
{
    public function __construct(private readonly DestinatariosTelegram $destinatarios) {}

    public function ticketNuevo(SupportTicket $ticket): void
    {
        $this->enviar('ticket_nuevo', $ticket, $this->destinatarios->soporte(), $ticket->user_id);
    }

    public function mensaje(SupportTicketMensaje $mensaje): void
    {
        $ticket = $mensaje->ticket;
        $chats = $mensaje->esDeSoporte()
            ? $this->destinatarios->empresa($ticket->empresa_id)
            : $this->destinatarios->soporte();

        $this->enviar('mensaje', $ticket, $chats, $mensaje->user_id, $mensaje);
    }

    public function estado(SupportTicket $ticket): void
    {
        $this->enviar('estado', $ticket, $this->destinatarios->empresa($ticket->empresa_id), null);
    }

    /** @param array<int,string> $chats */
    private function enviar(string $evento, SupportTicket $ticket, array $chats, ?int $autorId, ?SupportTicketMensaje $mensaje = null): void
    {
        $url = config('services.n8n.webhook_soporte');

        // Quien escribe no se avisa a sí mismo.
        if ($autorId) {
            $chats = array_values(array_diff($chats, $this->destinatarios->delUsuario($autorId)));
        }

        if (! $url || empty($chats)) {
            return;
        }

        $ticket->loadMissing('empresa', 'user');

        $payload = [
            'evento'        => $evento,
            'destinatarios' => $chats,
            'ticket'        => [
                'id'          => $ticket->id,
                'asunto'      => $ticket->asunto,
                'descripcion' => $ticket->descripcion,
                'prioridad'   => $ticket->prioridadLabel(),
                'estado'      => $ticket->statusLabel(),
                'empresa'     => $ticket->empresa?->name,
                'autor'       => $ticket->user?->name,
            ],
            'mensaje' => $mensaje ? [
                'texto'     => $mensaje->mensaje,
                'remitente' => $mensaje->remitente,
                'autor'     => $mensaje->user?->name,
                'adjunto'   => $mensaje->adjuntoPayload(),
            ] : null,
        ];
        $secreto = (string) config('n8n.secret');

        dispatch(function () use ($url, $secreto, $payload) {
            try {
                Http::timeout(5)->withHeaders(['X-N8N-Secret' => $secreto])->post($url, $payload);
            } catch (\Throwable $e) {
                Log::warning('No se pudo avisar a n8n de un evento de soporte', [
                    'evento' => $payload['evento'], 'ticket_id' => $payload['ticket']['id'], 'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }
}
