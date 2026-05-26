<?php

namespace App\Mail;

use App\Models\CmsAbout;
use App\Models\CmsHero;
use App\Models\Customer;
use App\Models\Empresa;
use App\Models\StoreCustomer;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Storage;

class WelcomeCustomerMail extends Mailable
{
    /**
     * @param StoreCustomer|Customer $customer  StoreCustomer (portal) o Customer (ERP)
     * @param Empresa                $empresa
     */
    public function __construct(
        public readonly StoreCustomer|Customer $customer,
        public readonly Empresa                $empresa,
    ) {}

    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: '[' . $this->empresa->name . '] ¡Bienvenido/a, es un placer trabajar contigo!'
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

        // ── Datos del cliente ──────────────────────────────────────────
        $esStoreCustomer = $customer instanceof StoreCustomer;
        $nombre          = $esStoreCustomer ? $customer->nombre_completo : $customer->nombre;
        $email           = $customer->email ?? '';
        $nombreCliente   = e($nombre);
        $emailCliente    = e($email);
        $empresaNombre   = e($empresa->name);

        // ── Logo ───────────────────────────────────────────────────────
        $logoHtml = '';
        if ($empresa->logo_path) {
            $logoUrl  = Storage::disk('public')->url($empresa->logo_path);
            $logoHtml = "<img src='{$logoUrl}' alt='{$empresaNombre}' style='max-height:52px;max-width:180px;object-fit:contain;display:block;margin:0 auto 12px;'>";
        }

        // ── Párrafo de relación comercial ──────────────────────────────
        $parrafo = $this->obtenerParrafoCms($empresa->id);

        // ── Credenciales (solo si es cliente del portal) ───────────────
        $credencialesBloque = '';
        if ($esStoreCustomer) {
            $passwordHint = $customer->cedula_ruc
                ? 'Tu contraseña inicial es tu número de cédula / RUC: <strong>' . e($customer->cedula_ruc) . '</strong>.'
                : 'Usa la contraseña que configuraste al registrarte.';

            $credencialesBloque = <<<HTML
  <tr>
    <td style="padding:0 40px 24px;">
      <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:20px 24px;">
        <p style="margin:0 0 10px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#166534;">
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
HTML;
        }

        // ── Botones: portal + web corporativa ─────────────────────────
        $portalUrl  = url('/tienda/' . $empresa->slug);
        $websiteUrl = $empresa->website_url ?? null;

        $botonesLinks = '<a href="' . $portalUrl . '" target="_blank"'
            . ' style="display:inline-block;background:#4f46e5;color:#ffffff;text-decoration:none;'
            . 'padding:13px 32px;border-radius:8px;font-size:14px;font-weight:700;margin:6px;">'
            . 'Ingresar al portal de clientes &rarr;</a>';

        if ($websiteUrl) {
            $websiteLabel = preg_replace('#^https?://#', '', rtrim($websiteUrl, '/'));
            $botonesLinks .= '<a href="' . e($websiteUrl) . '" target="_blank"'
                . ' style="display:inline-block;background:#0f172a;color:#ffffff;text-decoration:none;'
                . 'padding:13px 32px;border-radius:8px;font-size:14px;font-weight:700;margin:6px;">'
                . '&#127760; ' . e($websiteLabel) . '</a>';
        }

        $botonesRow = $botonesLinks
            ? '<tr><td style="padding:0 40px 32px;text-align:center;">' . $botonesLinks . '</td></tr>'
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f1f5f9;padding:40px 16px;">
<tr><td align="center">
<table width="580" cellpadding="0" cellspacing="0" border="0"
       style="max-width:580px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,.09);">

  <!-- Cabecera -->
  <tr>
    <td style="background:linear-gradient(135deg,#1e3a8a 0%,#1e293b 100%);padding:36px 40px 28px;text-align:center;">
      {$logoHtml}
      <p style="margin:0;color:rgba(255,255,255,.5);font-size:11px;letter-spacing:1.8px;text-transform:uppercase;">
        {$empresaNombre}
      </p>
    </td>
  </tr>

  <!-- Saludo -->
  <tr>
    <td style="padding:38px 40px 0;text-align:center;">
      <div style="display:inline-block;background:#eef2ff;border:1px solid #c7d2fe;border-radius:999px;
                  padding:6px 22px;font-size:13px;font-weight:700;color:#4f46e5;margin-bottom:18px;">
        ¡Bienvenido/a a nuestra familia!
      </div>
      <p style="margin:0;font-size:23px;font-weight:800;color:#1e293b;line-height:1.25;">
        Hola, {$nombreCliente}
      </p>
      <p style="margin:10px 0 0;font-size:14px;color:#64748b;line-height:1.7;">
        Es un placer tenerte como cliente de <strong style="color:#1e293b;">{$empresaNombre}</strong>.
        Hoy comienza una relación que esperamos sea larga, sólida y llena de éxitos compartidos.
      </p>
    </td>
  </tr>

  <!-- Mensaje de relación comercial -->
  <tr>
    <td style="padding:26px 40px 22px;">
      <div style="background:#f8fafc;border-left:4px solid #4f46e5;border-radius:0 10px 10px 0;padding:20px 22px;">
        <p style="margin:0 0 10px;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#4f46e5;">
          Nuestra promesa contigo
        </p>
        <p style="margin:0;font-size:14px;color:#334155;line-height:1.85;">
          {$parrafo}
        </p>
      </div>
    </td>
  </tr>

  <!-- Bloque de relación a largo plazo -->
  <tr>
    <td style="padding:0 40px 24px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="width:33%;padding:12px 10px 12px 0;vertical-align:top;">
            <div style="background:#fafafa;border:1px solid #f1f5f9;border-radius:10px;padding:16px 14px;text-align:center;">
              <div style="font-size:22px;margin-bottom:6px;">🤝</div>
              <p style="margin:0;font-size:12px;font-weight:700;color:#1e293b;">Alianza estratégica</p>
              <p style="margin:4px 0 0;font-size:11px;color:#64748b;line-height:1.5;">Más que una transacción, construimos una relación de negocios duradera.</p>
            </div>
          </td>
          <td style="width:33%;padding:12px 5px;vertical-align:top;">
            <div style="background:#fafafa;border:1px solid #f1f5f9;border-radius:10px;padding:16px 14px;text-align:center;">
              <div style="font-size:22px;margin-bottom:6px;">⭐</div>
              <p style="margin:0;font-size:12px;font-weight:700;color:#1e293b;">Servicio de calidad</p>
              <p style="margin:4px 0 0;font-size:11px;color:#64748b;line-height:1.5;">Cada entrega, cada detalle, cada interacción refleja nuestro compromiso contigo.</p>
            </div>
          </td>
          <td style="width:33%;padding:12px 0 12px 10px;vertical-align:top;">
            <div style="background:#fafafa;border:1px solid #f1f5f9;border-radius:10px;padding:16px 14px;text-align:center;">
              <div style="font-size:22px;margin-bottom:6px;">📈</div>
              <p style="margin:0;font-size:12px;font-weight:700;color:#1e293b;">Crecimiento mutuo</p>
              <p style="margin:4px 0 0;font-size:11px;color:#64748b;line-height:1.5;">Tu éxito es nuestro éxito. Trabajamos juntos para crecer sostenidamente.</p>
            </div>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  {$credencialesBloque}

  {$botonesRow}

  <!-- Pie -->
  <tr>
    <td style="background:#f8fafc;padding:18px 40px;text-align:center;border-top:1px solid #e2e8f0;">
      <p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.7;">
        Este correo fue enviado por <strong style="color:#64748b;">{$empresaNombre}</strong>.<br>
        Si tienes alguna consulta, responde directamente a este mensaje y con gusto te atendemos.
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
        $about = CmsAbout::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('activo', true)
            ->first();

        if ($about && $about->descripcion) {
            return e($about->descripcion);
        }

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

        return 'Gracias por confiar en nosotros. Esta es solo el inicio de una relación comercial '
            . 'que esperamos sea larga y fructífera. Nos comprometemos a ofrecerte siempre el mejor '
            . 'producto, el mejor servicio y la mejor experiencia. Tu confianza nos impulsa a mejorar '
            . 'cada día, y estamos aquí para acompañarte en cada paso de tu negocio.';
    }
}
