<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\Empresa;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CustomerResetPasswordMail extends Mailable
{
    public function __construct(
        public readonly Customer $customer,
        public readonly Empresa  $empresa,
        public readonly string   $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . $this->empresa->name . '] Recupera tu contraseña'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-token',
            with: [
                'empresa'   => $this->empresa,
                'nombre'    => $this->customer->nombre_completo ?: $this->customer->nombre,
                'titulo'    => 'Recupera tu contraseña',
                'mensaje'   => 'Recibimos una solicitud para cambiar la contraseña de tu cuenta en '
                             . $this->empresa->name . '. Pulsa el botón para elegir una nueva.',
                'boton'     => 'Cambiar mi contraseña',
                'url'       => $this->url,
                'caducidad' => 'El enlace caduca en 1 hora y solo se puede usar una vez.',
            ],
        );
    }
}
