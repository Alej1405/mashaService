<?php

namespace App\Modules\Soporte\Actions;

use App\Filament\Admin\Resources\SoporteTicketResource;
use App\Filament\App\Resources\SupportTicketResource;
use App\Models\SupportTicket;
use App\Models\SupportTicketMensaje;
use App\Models\User;
use App\Modules\Soporte\Queries\DestinatariosSoporte;
use App\Shared\Attributes\Documentado;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Avisa los eventos de soporte por dos canales:
 * - la campana del ERP, para todos los destinatarios;
 * - Telegram (vía n8n), para los que ligaron su número.
 * Lo que dice la empresa va a soporte; lo que dice soporte va a la empresa.
 * Telegram sale después de responder al usuario: si n8n tarda o está caído, la
 * pantalla no espera y el ticket se guarda igual (solo queda el aviso en el log).
 */
#[Documentado(
    grupo: 'Soporte',
    descripcion: 'Avisa tickets nuevos, mensajes y cambios de estado en la campana del ERP y por Telegram.',
    tipo: 'action',
)]
final class NotificarSoporte
{
    private const PARA_SOPORTE = 'soporte';
    private const PARA_EMPRESA = 'empresa';

    public function __construct(private readonly DestinatariosSoporte $destinatarios) {}

    public function ticketNuevo(SupportTicket $ticket): void
    {
        $this->enviar('ticket_nuevo', $ticket, self::PARA_SOPORTE, $ticket->user_id);
    }

    public function mensaje(SupportTicketMensaje $mensaje): void
    {
        $para = $mensaje->esDeSoporte() ? self::PARA_EMPRESA : self::PARA_SOPORTE;

        $this->enviar('mensaje', $mensaje->ticket, $para, $mensaje->user_id, $mensaje);
    }

    public function estado(SupportTicket $ticket): void
    {
        $this->enviar('estado', $ticket, self::PARA_EMPRESA, null);
    }

    private function enviar(string $evento, SupportTicket $ticket, string $para, ?int $autorId, ?SupportTicketMensaje $mensaje = null): void
    {
        $ticket->loadMissing('empresa', 'user');

        // Quien escribe no se avisa a sí mismo.
        $usuarios = $this->usuarios($ticket, $para)->reject(fn (User $u) => $u->id === $autorId)->values();
        if ($usuarios->isEmpty()) {
            return;
        }

        $this->campana($evento, $ticket, $para, $usuarios, $mensaje);
        $this->telegram($evento, $ticket, $this->destinatarios->chats($usuarios), $mensaje);
    }

    /** @return Collection<int,User> */
    private function usuarios(SupportTicket $ticket, string $para): Collection
    {
        return $para === self::PARA_SOPORTE
            ? $this->destinatarios->soporte()
            : $this->destinatarios->empresa($ticket->empresa_id);
    }

    /** @param Collection<int,User> $usuarios */
    private function campana(string $evento, SupportTicket $ticket, string $para, Collection $usuarios, ?SupportTicketMensaje $mensaje): void
    {
        $titulo = match ($evento) {
            'ticket_nuevo' => 'Ticket nuevo #'.$ticket->id.' · '.$ticket->empresa?->name,
            'mensaje'      => 'Nueva respuesta en el ticket #'.$ticket->id,
            default        => 'Ticket #'.$ticket->id.': '.$ticket->statusLabel(),
        };

        $cuerpo = $ticket->asunto;
        if ($mensaje) {
            $cuerpo .= ' — '.($mensaje->mensaje ? Str::limit($mensaje->mensaje, 120) : 'Envió un adjunto: '.$mensaje->adjunto_nombre);
        }

        Notification::make()
            ->title($titulo)
            ->body($cuerpo)
            ->icon('heroicon-o-lifebuoy')
            ->iconColor($evento === 'ticket_nuevo' ? 'danger' : 'info')
            ->actions([
                Action::make('ver')->label('Ver ticket')->url($this->urlDelTicket($ticket, $para))->markAsRead(),
            ])
            ->sendToDatabase($usuarios);
    }

    /** Soporte lo atiende en el admin; la empresa lo ve en su panel. */
    private function urlDelTicket(SupportTicket $ticket, string $para): string
    {
        if ($para === self::PARA_SOPORTE) {
            return SoporteTicketResource::getUrl('view', ['record' => $ticket], panel: 'admin');
        }

        return SupportTicketResource::getUrl('view', ['record' => $ticket], panel: 'basic', tenant: $ticket->empresa);
    }

    /** @param array<int,string> $chats */
    private function telegram(string $evento, SupportTicket $ticket, array $chats, ?SupportTicketMensaje $mensaje): void
    {
        $url = config('services.n8n.webhook_soporte');
        if (! $url || empty($chats)) {
            return;
        }

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
