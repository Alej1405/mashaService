<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Alguien pidió acceso al ERP desde la pantalla de ingreso.
 *
 * No crea usuario ni empresa: el ERP no tiene registro abierto. Esto es el
 * aviso para que alguien de MashaCorp lo configure a mano y se comunique.
 */
class SolicitudAccesoMail extends Mailable
{
    public function __construct(
        public readonly string  $nombre,
        public readonly string  $empresa,
        public readonly string  $correo,
        public readonly ?string $telefono = null,
        public readonly ?string $mensaje = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de acceso al ERP · ' . $this->empresa,
            replyTo: [$this->correo],
        );
    }

    public function content(): \Illuminate\Mail\Mailables\Content
    {
        return new \Illuminate\Mail\Mailables\Content(
            htmlString: $this->buildHtml(),
        );
    }

    public function buildHtml(): string
    {
        $filas = [
            'Nombre'   => $this->nombre,
            'Empresa'  => $this->empresa,
            'Correo'   => $this->correo,
            'Teléfono' => $this->telefono ?: '—',
            'Fecha'    => now()->format('d/m/Y H:i'),
        ];

        $html = '';
        foreach ($filas as $etiqueta => $valor) {
            $html .= '<tr>'
                . '<td style="padding:8px 0;color:#64748b;font-size:13px;width:110px">' . e($etiqueta) . '</td>'
                . '<td style="padding:8px 0;color:#0f172a;font-size:14px;font-weight:700">' . e($valor) . '</td>'
                . '</tr>';
        }

        $mensaje = $this->mensaje
            ? '<p style="margin:18px 0 0;padding:14px 16px;background:#f8fafc;border-radius:12px;'
              . 'color:#334155;font-size:14px;line-height:1.5">' . nl2br(e($this->mensaje)) . '</p>'
            : '';

        return '<div style="font-family:ui-sans-serif,system-ui,sans-serif;max-width:560px;margin:0 auto;padding:28px">'
            . '<p style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#4f46e5;margin:0">'
            . 'ERP Masha.net</p>'
            . '<h1 style="font-size:22px;color:#0f172a;margin:6px 0 4px">Solicitud de acceso</h1>'
            . '<p style="font-size:14px;color:#64748b;margin:0 0 18px">'
            . 'Alguien pidió unirse al ERP desde la pantalla de ingreso. Responde a este correo para contactarlo.</p>'
            . '<table style="width:100%;border-collapse:collapse;border-top:1px solid #e2e8f0">' . $html . '</table>'
            . $mensaje
            . '</div>';
    }
}
