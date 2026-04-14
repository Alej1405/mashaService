<?php

namespace App\Jobs;

use App\Models\Empresa;
use App\Models\MailCampaign;
use App\Models\MailingContact;
use App\Services\MailingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMailCampaignJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 1;
    public int $timeout = 86400; // 24 h — listas grandes requieren horas de envío

    /**
     * Límite de correos por hora que admite el servidor de salida.
     * Se deja un margen del 5 % para absorber latencias del servidor.
     */
    private const RATE_LIMIT_PER_HOUR = 95;

    public function __construct(
        private readonly int $campaignId,
        private readonly int $empresaId,
    ) {}

    public function handle(): void
    {
        $empresa  = Empresa::find($this->empresaId);
        $campaign = MailCampaign::find($this->campaignId);

        if (! $empresa || ! $campaign || ! $campaign->mailTemplate) {
            return;
        }

        $query = MailingContact::where('empresa_id', $this->empresaId)
            ->where('active', true)
            ->select('nombre', 'email');

        if ($campaign->mailing_group_id) {
            $query->where('mailing_group_id', $campaign->mailing_group_id);
        }

        if ($query->count() === 0) {
            $campaign->update([
                'status'    => 'failed',
                'error_log' => 'No se encontraron contactos activos al momento de enviar.',
                'sent_at'   => now(),
            ]);
            return;
        }

        $service     = new MailingService($empresa);
        $totalSent   = 0;
        $totalFailed = 0;

        // ── Ventana de rate limiting ──────────────────────────────────────────
        // Registramos el inicio de la ventana de 1 hora y cuántos correos
        // hemos enviado en ella. Cuando llegamos al límite, dormimos hasta
        // que la ventana se reinicie antes de continuar.
        $windowStart    = microtime(true);
        $sentThisWindow = 0;

        $query->cursor()->each(function ($contact) use (
            $service, $campaign, &$totalSent, &$totalFailed,
            &$windowStart, &$sentThisWindow
        ) {
            // ── Comprobar ventana antes de enviar ─────────────────────────
            if ($sentThisWindow >= self::RATE_LIMIT_PER_HOUR) {
                $elapsed   = microtime(true) - $windowStart;
                $remaining = 3600 - $elapsed;

                if ($remaining > 0) {
                    // Dormimos los segundos que faltan para completar la hora,
                    // más 5 segundos de buffer para evitar llegar exactamente al límite.
                    sleep((int) ceil($remaining) + 5);
                }

                // Reiniciar ventana
                $windowStart    = microtime(true);
                $sentThisWindow = 0;
            }

            // ── Enviar UN correo a UN destinatario ────────────────────────
            $result = $service->sendSingleEmail(
                ['nombre' => $contact->nombre, 'email' => $contact->email],
                $campaign->mailTemplate,
            );

            if ($result['success']) {
                $totalSent++;
                $sentThisWindow++;
            } else {
                $totalFailed++;
            }

            // Persistir progreso en tiempo real para que el dashboard lo refleje
            $campaign->update([
                'sent_count'   => $totalSent,
                'failed_count' => $totalFailed,
            ]);
        });

        $campaign->update([
            'status'    => $totalSent === 0 ? 'failed' : 'sent',
            'sent_at'   => now(),
            'error_log' => $totalFailed === 0 ? null : "Fallidos: {$totalFailed}",
        ]);
    }

    public function failed(\Throwable $e): void
    {
        $campaign = MailCampaign::find($this->campaignId);

        $campaign?->update([
            'status'    => 'failed',
            'error_log' => $e->getMessage(),
            'sent_at'   => now(),
        ]);
    }
}
