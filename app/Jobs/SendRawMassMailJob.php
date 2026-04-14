<?php

namespace App\Jobs;

use App\Models\Empresa;
use App\Models\MailingSendLog;
use App\Services\MailingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Envía un correo con HTML arbitrario a una lista de contactos,
 * de uno en uno, respetando el límite de 100 correos por hora del SMTP
 * y omitiendo destinatarios que ya recibieron este correo recientemente.
 */
class SendRawMassMailJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 1;
    public int $timeout = 86400; // 24 h

    private const RATE_LIMIT_PER_HOUR = 95;

    /**
     * @param array<array{nombre: string, email: string}> $contacts
     * @param string  $tipo         MailingSendLog::TIPO_*
     * @param int|null $referenciaId ID del post, campaña, etc. (null para carta)
     */
    public function __construct(
        private readonly int     $empresaId,
        private readonly string  $subject,
        private readonly string  $html,
        private readonly array   $contacts,
        private readonly string  $tipo        = MailingSendLog::TIPO_CARTA,
        private readonly ?int    $referenciaId = null,
    ) {}

    public function handle(): void
    {
        $empresa = Empresa::find($this->empresaId);

        if (! $empresa) {
            return;
        }

        $service = new MailingService($empresa);

        $windowStart    = microtime(true);
        $sentThisWindow = 0;

        foreach ($this->contacts as $contact) {
            $email  = strtolower(trim($contact['email'] ?? ''));
            $nombre = $contact['nombre'] ?? $email;

            if (empty($email)) {
                continue;
            }

            // ── Deduplicación: omitir si ya se envió ─────────────────────
            if (MailingSendLog::yaEnviado($this->empresaId, $email, $this->tipo, $this->referenciaId)) {
                continue;
            }

            // ── Control de ventana horaria ────────────────────────────────
            if ($sentThisWindow >= self::RATE_LIMIT_PER_HOUR) {
                $elapsed   = microtime(true) - $windowStart;
                $remaining = 3600 - $elapsed;

                if ($remaining > 0) {
                    sleep((int) ceil($remaining) + 5);
                }

                $windowStart    = microtime(true);
                $sentThisWindow = 0;
            }

            // ── Enviar a UN único destinatario ────────────────────────────
            $result = $service->sendSingleRawEmail($email, $nombre, $this->subject, $this->html);

            if ($result['success']) {
                MailingSendLog::registrar($this->empresaId, $email, $this->tipo, $this->referenciaId);
                $sentThisWindow++;
            }
        }
    }
}
