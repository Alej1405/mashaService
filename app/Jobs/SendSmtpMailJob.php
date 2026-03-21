<?php

namespace App\Jobs;

use App\Mail\EmpresaPlainMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendSmtpMailJob implements ShouldQueue
{
    use Queueable;

    /** Reintentos ante fallo (ej. timeout puntual). */
    public int $tries = 2;

    /** Tiempo máximo de ejecución en segundos. */
    public int $timeout = 30;

    public function __construct(
        private readonly array  $smtpConfig,
        private readonly string $toEmail,
        private readonly string $toName,
        private readonly string $subject,
        private readonly string $html,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {}

    public function handle(): void
    {
        config(['mail.mailers.job_smtp' => $this->smtpConfig]);

        Mail::mailer('job_smtp')
            ->to($this->toEmail, $this->toName)
            ->send(new EmpresaPlainMail(
                $this->subject,
                $this->html,
                $this->fromEmail,
                $this->fromName,
            ));
    }
}
