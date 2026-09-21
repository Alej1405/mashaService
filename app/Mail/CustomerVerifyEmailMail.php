<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\Empresa;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CustomerVerifyEmailMail extends Mailable
{
    public function __construct(
        public readonly Customer $customer,
        public readonly Empresa  $empresa,
        public readonly string   $url,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . $this->empresa->name . '] Verifica tu correo para activar tu cuenta'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-token',
            with: [
                'empresa'   => $this->empresa,
                'nombre'    => $this->customer->nombre_completo ?: $this->customer->nombre,
                'titulo'    => 'Verifica tu correo',
                'mensaje'   => 'Creaste una cuenta en la tienda de ' . $this->empresa->name
                             . '. Confirma que este correo es tuyo para poder iniciar sesión.',
                'boton'     => 'Verificar mi correo',
                'url'       => $this->url,
                'caducidad' => null,
            ],
        );
    }
}
