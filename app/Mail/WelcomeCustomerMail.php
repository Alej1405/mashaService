<?php

namespace App\Mail;

use App\Models\CmsAbout;
use App\Models\CmsHero;
use App\Models\Empresa;
use App\Models\StoreCustomer;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Storage;

class WelcomeCustomerMail extends Mailable
{
    public function __construct(
        public readonly StoreCustomer $customer,
        public readonly Empresa       $empresa,
    ) {}

    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: '[' . $this->empresa->name . '] ¡Bienvenido/a a nuestro portal!'
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
        $empresa  = $this->empresa;
        $customer = $this->customer;

        // Logo
        $logoHtml = '';
        if ($empresa->logo_path) {
            $logoUrl  = Storage::disk('public')->url($empresa->logo_path);
            $logoHtml = "<img src='{$logoUrl}' alt='" . e($empresa->name) . "' style='max-height:48px;max-width:170px;object-fit:contain;display:block;margin:0 auto 12px;'>";
        }

        // Párrafo de bienvenida desde CMS
        $parrafo = $this->obtenerParrafoCms($empresa->id);

        // Credenciales
        $passwordHint = $customer->cedula_ruc
            ? 'Tu contraseña inicial es tu número de cédula / RUC: <strong>' . e($customer->cedula_ruc) . '</strong>'
            : 'Usa la contraseña que configuraste al registrarte.';

        $portalUrl = url('/tienda/' . $empresa->slug);

        $nombreCliente = e($customer->nombre_completo);
        $emailCliente  = e($customer->email);
        $empresaNombre = e($empresa->name);

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f1f5f9;padding:40px 16px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" border="0"
       style="max-width:560px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08);">

  <!-- Cabecera -->
  <tr>
    <td style="background:#1e293b;padding:32px 40px 24px;text-align:center;">
      {$logoHtml}
      <p style="margin:0;color:rgba(255,255,255,.45);font-size:11px;letter-spacing:1.5px;text-transform:uppercase;">
        {$empresaNombre}
      </p>
    </td>
  </tr>

  <!-- Saludo principal -->
  <tr>
    <td style="padding:36px 40px 0;text-align:center;">
      <div style="display:inline-block;background:#4f46e518;border:1px solid #4f46e544;border-radius:999px;
                  padding:6px 20px;font-size:13px;font-weight:700;color:#4f46e5;margin-bottom:20px;">
        ¡Bienvenido/a!
      </div>
      <p style="margin:0;font-size:22px;font-weight:800;color:#1e293b;line-height:1.3;">
        Hola, {$nombreCliente}
      </p>
      <p style="margin:10px 0 0;font-size:14px;color:#64748b;line-height:1.7;">
        Tu cuenta ha sido creada exitosamente en <strong style="color:#1e293b;">{$empresaNombre}</strong>.
      </p>
    </td>
  </tr>

  <!-- Párrafo emotivo del CMS -->
  <tr>
    <td style="padding:24px 40px;">
      <div style="background:#f8fafc;border-left:4px solid #4f46e5;border-radius:0 8px 8px 0;padding:18px 20px;">
        <p style="margin:0;font-size:14px;color:#334155;line-height:1.8;">
          {$parrafo}
        </p>
      </div>
    </td>
  </tr>

  <!-- Acceso al portal -->
  <tr>
    <td style="padding:0 40px 24px;">
      <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:20px 24px;">
        <p style="margin:0 0 6px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#166534;">
          Tus datos de acceso
        </p>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:13px;">
          <tr>
            <td style="padding:4px 0;color:#4b5563;white-space:nowrap;vertical-align:top;width:100px;">Correo:</td>
            <td style="padding:4px 0;color:#1e293b;font-weight:700;">{$emailCliente}</td>
          </tr>
          <tr>
            <td style="padding:4px 0;color:#4b5563;vertical-align:top;">Contraseña:</td>
            <td style="padding:4px 0;color:#1e293b;">{$passwordHint}</td>
          </tr>
        </table>
      </div>
    </td>
  </tr>

  <!-- CTA -->
  <tr>
    <td style="padding:0 40px 32px;text-align:center;">
      <a href="{$portalUrl}" target="_blank"
         style="display:inline-block;background:#4f46e5;color:#ffffff;text-decoration:none;
                padding:14px 36px;border-radius:8px;font-size:15px;font-weight:700;">
        Ingresar al portal →
      </a>
    </td>
  </tr>

  <!-- Pie -->
  <tr>
    <td style="background:#f8fafc;padding:16px 40px;text-align:center;border-top:1px solid #e2e8f0;">
      <p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.7;">
        Este correo fue enviado por <strong style="color:#64748b;">{$empresaNombre}</strong>.
        Si tienes alguna duda, responde a este mensaje.
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    private function obtenerParrafoCms(int $empresaId): string
    {
        // Intentar con CmsAbout
        $about = CmsAbout::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('activo', true)
            ->first();

        if ($about && $about->descripcion) {
            return e($about->descripcion);
        }

        // Fallback a CmsHero
        $hero = CmsHero::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('activo', true)
            ->first();

        if ($hero && $hero->descripcion) {
            return e($hero->descripcion);
        }

        if ($hero && $hero->subtitulo) {
            return e($hero->subtitulo);
        }

        // Texto genérico
        return 'Estamos comprometidos a brindarte un servicio de excelencia, con la solidez y confianza que mereces. '
            . 'Tu satisfacción es nuestra mayor prioridad y trabajamos cada día para garantizar que tus cargas lleguen '
            . 'seguras y a tiempo. Cuenta con nosotros como tu aliado logístico de confianza.';
    }
}
