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
    public int $timeout = 3600; // hasta 1h para listas grandes

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

        // Si la campaña tiene grupo asignado, enviar solo a ese grupo
        if ($campaign->mailing_group_id) {
            $query->where('mailing_group_id', $campaign->mailing_group_id);
        }

        // Contar sin traer todos los registros a memoria
        if ($query->count() === 0) {
            $campaign->update([
                'status'    => 'failed',
                'error_log' => 'No se encontraron contactos activos al momento de enviar.',
                'sent_at'   => now(),
            ]);
            return;
        }

        // Cargar en lotes de 1000 para no agotar memoria con listas grandes
        $totalSent   = 0;
        $totalFailed = 0;
        $chunkIndex  = 0;
        $service     = new MailingService($empresa);

        $query->chunk(1000, function ($batch) use ($service, $campaign, &$totalSent, &$totalFailed, &$chunkIndex) {
            if ($chunkIndex > 0) {
                sleep(1); // 1 segundo entre llamadas a la API para no saturar la cuota por minuto
            }
            $contacts = $batch->map(fn ($c) => ['nombre' => $c->nombre, 'email' => $c->email])->toArray();
            $result   = $service->sendMassEmail($contacts, $campaign->mailTemplate);
            $totalSent   += $result['sent'];
            $totalFailed += $result['failed'];
            $chunkIndex++;
        });

        $campaign->update([
            'status'       => $totalFailed === 0 ? 'sent' : ($totalSent === 0 ? 'failed' : 'sent'),
            'sent_count'   => $totalSent,
            'failed_count' => $totalFailed,
            'sent_at'      => now(),
            'error_log'    => $totalFailed === 0 ? null : "Fallidos: {$totalFailed}",
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
