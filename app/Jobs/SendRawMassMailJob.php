<?php

namespace App\Jobs;

use App\Models\Empresa;
use App\Models\MailCampaign;
use App\Models\MailingSendLog;
use App\Services\MailingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendRawMassMailJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 1;
    public int $timeout = 86400; // 24 h

    private const RATE_LIMIT_PER_HOUR = 95;

    /**
     * @param array<array{nombre: string, email: string}> $contacts
     * @param string   $tipo          MailingSendLog::TIPO_*
     * @param int|null $referenciaId  ID del post, campaña, etc. (null para carta sin campaña)
     * @param int|null $campaignId    ID en mail_campaigns para actualizar progreso en tiempo real
     */
    public function __construct(
        private readonly int     $empresaId,
        private readonly string  $subject,
        private readonly string  $html,
        private readonly array   $contacts,
        private readonly string  $tipo         = MailingSendLog::TIPO_CARTA,
        private readonly ?int    $referenciaId  = null,
        private readonly ?int    $campaignId    = null,
    ) {}

    public function handle(): void
    {
        $empresa = Empresa::find($this->empresaId);

        if (! $empresa) {
            return;
        }

        $service = new MailingService($empresa);

        // Distribución uniforme: 1 email cada N segundos para no generar ráfagas
        // que activen el rate-limit de Mailgun. El techo horario sigue activo
        // como red de seguridad ante desfases de reloj.
        $sleepPerEmail  = (int) floor(3600 / self::RATE_LIMIT_PER_HOUR); // 37 s para 95/h
        $windowStart    = microtime(true);
        $sentThisWindow = 0;
        $totalSent      = 0;
        $totalFailed    = 0;

        foreach ($this->contacts as $contact) {
            $email  = strtolower(trim($contact['email'] ?? ''));
            $nombre = $contact['nombre'] ?? $email;

            if (empty($email)) {
                continue;
            }

            if (MailingSendLog::yaEnviado($this->empresaId, $email, $this->tipo, $this->referenciaId)) {
                continue;
            }

            // Techo de seguridad: si la distribución uniforme acumula más de
            // RATE_LIMIT_PER_HOUR en la ventana actual, esperar hasta que se renueve.
            if ($sentThisWindow >= self::RATE_LIMIT_PER_HOUR) {
                $elapsed   = microtime(true) - $windowStart;
                $remaining = 3600 - $elapsed;

                if ($remaining > 0) {
                    sleep((int) ceil($remaining) + 5);
                }

                $windowStart    = microtime(true);
                $sentThisWindow = 0;
            }

            $result = $service->sendSingleRawEmail($email, $nombre, $this->subject, $this->html);

            // Pausa uniforme después de cada llamada a la API (éxito o fallo)
            // para distribuir las peticiones a lo largo de la hora.
            sleep($sleepPerEmail);

            if ($result['success']) {
                MailingSendLog::registrar($this->empresaId, $email, $this->tipo, $this->referenciaId);
                $sentThisWindow++;
                $totalSent++;

                if ($this->campaignId) {
                    MailCampaign::where('id', $this->campaignId)->increment('sent_count');
                }
            } else {
                $totalFailed++;

                if ($this->campaignId) {
                    MailCampaign::where('id', $this->campaignId)->increment('failed_count');
                }
            }
        }

        if ($this->campaignId) {
            MailCampaign::where('id', $this->campaignId)->update([
                'status'    => $totalSent === 0 ? 'failed' : 'sent',
                'sent_at'   => now(),
                'error_log' => $totalFailed > 0 ? "Fallidos: {$totalFailed}" : null,
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        if ($this->campaignId) {
            MailCampaign::where('id', $this->campaignId)->update([
                'status'    => 'failed',
                'error_log' => $e->getMessage(),
                'sent_at'   => now(),
            ]);
        }
    }
}
