<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;

/**
 * Mailable genérico para enviar HTML plano desde MailingService via SMTP.
 */
class EmpresaPlainMail extends Mailable
{
    public function __construct(
        private string $mailSubject,
        private string $htmlContent,
        private string $fromAddress,
        private string $fromName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
            from: new Address($this->fromAddress, $this->fromName),
        );
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->htmlContent);
    }
}
