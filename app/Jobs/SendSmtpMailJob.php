<?php

namespace App\Jobs;

use App\Models\Empresa;
use App\Services\MailingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSmtpMailJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 2;
    public int $timeout = 30;

    public function __construct(
        private readonly int    $empresaId,
        private readonly string $toEmail,
        private readonly string $toName,
        private readonly string $subject,
        private readonly string $html,
    ) {}

    public function handle(): void
    {
        $empresa = Empresa::find($this->empresaId);

        if (! $empresa) {
            return;
        }

        $result = (new MailingService($empresa))
            ->sendRawEmail($this->toEmail, $this->toName, $this->subject, $this->html);

        if (! $result['success']) {
            throw new \Exception($result['message']);
        }
    }
}
