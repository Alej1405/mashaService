<?php

namespace App\Mail;

use App\Models\Empresa;
use App\Models\LogisticsPackage;
use App\Models\LogisticsShipment;
use App\Models\StoreCustomer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class LogisticsPackageStatusMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly LogisticsPackage $package,
        public readonly StoreCustomer    $customer,
        public readonly Empresa          $empresa,
    ) {}

    public function envelope(): Envelope
    {
        $estadoLabel = LogisticsPackage::ESTADOS[$this->package->estado] ?? $this->package->estado;

        return new Envelope(
            subject: '[' . $this->empresa->name . '] Tu carga: ' . $estadoLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    // ── HTML del correo ──────────────────────────────────────────────────────

    public function buildHtml(): string
    {
        $empresa   = $this->empresa;
        $package   = $this->package;
        $customer  = $this->customer;

        $estadoLabel = LogisticsPackage::ESTADOS[$package->estado] ?? $package->estado;

        // Estado del embarque (si existe)
        $shipment      = $package->shipments()->latest()->first();
        $estadoEmb     = $shipment ? (LogisticsShipment::ESTADOS[$shipment->estado] ?? null) : null;
        $colorEmb      = $estadoEmb['color'] ?? '#6366f1';
        $estadoEmbLabel = $estadoEmb['label'] ?? null;

        // Barra de progreso del embarque
        $progresoPct = 0;
        if ($shipment) {
            $estados    = array_keys(LogisticsShipment::ESTADOS);
            $idx        = array_search($shipment->estado, $estados);
            $total      = count($estados);
            $progresoPct = $total > 1 ? round(($idx / ($total - 1)) * 100) : 0;
        }

        // Logo
        $logoHtml = '';
        if ($empresa->logo_path) {
            $logoUrl  = Storage::disk('public')->url($empresa->logo_path);
            $logoHtml = "<img src='{$logoUrl}' alt='" . e($empresa->name) . "' style='max-height:44px;max-width:160px;object-fit:contain;display:block;margin:0 auto 12px;'>";
        }

        // Link al portal
        $portalUrl = url('/tienda/' . $empresa->slug);

        // Credenciales en texto pequeño
        $passwordHint = $customer->cedula_ruc
            ? 'Tu contraseña es tu número de cédula / RUC: <strong>' . e($customer->cedula_ruc) . '</strong>'
            : 'Usa la contraseña que te fue asignada.';

        // Filas de detalle del paquete
        $detalles = '';
        if ($package->numero_tracking) {
            $detalles .= $this->fila('Tracking', e($package->numero_tracking));
        }
        if ($package->descripcion) {
            $detalles .= $this->fila('Contenido', e($package->descripcion));
        }
        if ($package->peso_kg) {
            $detalles .= $this->fila('Peso', $package->peso_kg . ' kg');
        }
        if ($package->valor_declarado) {
            $detalles .= $this->fila('Valor declarado', '$' . number_format($package->valor_declarado, 2));
        }
        if ($shipment) {
            $detalles .= $this->fila('Embarque', e($shipment->numero_embarque));
        }
        if ($shipment?->fecha_llegada_ecuador) {
            $detalles .= $this->fila('Llegada estimada', $shipment->fecha_llegada_ecuador->format('d/m/Y'));
        }

        // Sección del estado del embarque
        $embarqueHtml = '';
        if ($shipment && $estadoEmbLabel) {
            $embarqueHtml = <<<HTML
  <tr>
    <td style="padding:0 40px 28px;">
      <div style="background:#f8fafc;border-radius:8px;padding:16px 20px;">
        <p style="margin:0 0 10px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#94a3b8;">
          Estado del embarque
        </p>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
          <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{$colorEmb};"></span>
          <span style="font-size:14px;font-weight:700;color:{$colorEmb};">{$estadoEmbLabel}</span>
        </div>
        <div style="background:#e2e8f0;border-radius:4px;height:6px;overflow:hidden;">
          <div style="height:6px;width:{$progresoPct}%;background:{$colorEmb};border-radius:4px;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;margin-top:4px;">
          <span style="font-size:10px;color:#94a3b8;">Solicitado</span>
          <span style="font-size:10px;color:#94a3b8;">{$progresoPct}%</span>
          <span style="font-size:10px;color:#94a3b8;">Entregado</span>
        </div>
      </div>
    </td>
  </tr>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f1f5f9;padding:40px 16px;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" border="0"
       style="max-width:560px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08);">

  {{-- Header --}}
  <tr>
    <td style="background:#1e293b;padding:32px 40px 24px;text-align:center;">
      {$logoHtml}
      <p style="margin:0;color:rgba(255,255,255,.45);font-size:11px;letter-spacing:1.5px;text-transform:uppercase;">
        {$empresa->name}
      </p>
    </td>
  </tr>

  {{-- Badge de estado --}}
  <tr>
    <td style="padding:28px 40px 4px;text-align:center;">
      <span style="display:inline-block;background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;
                   border-radius:999px;padding:6px 18px;font-size:13px;font-weight:700;">
        {$estadoLabel}
      </span>
    </td>
  </tr>

  {{-- Saludo --}}
  <tr>
    <td style="padding:20px 40px 8px;">
      <p style="margin:0;font-size:17px;font-weight:700;color:#1e293b;">
        Hola, {$customer->nombre_completo}
      </p>
      <p style="margin:8px 0 0;font-size:14px;color:#475569;line-height:1.6;">
        Te informamos sobre el estado de tu paquete.
      </p>
    </td>
  </tr>

  {{-- Detalles del paquete --}}
  <tr>
    <td style="padding:20px 40px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;font-size:13px;">
        {$detalles}
      </table>
    </td>
  </tr>

  {$embarqueHtml}

  {{-- Botón portal --}}
  <tr>
    <td style="padding:4px 40px 28px;text-align:center;">
      <a href="{$portalUrl}" target="_blank"
         style="display:inline-block;background:#4f46e5;color:#ffffff;text-decoration:none;
                padding:12px 32px;border-radius:8px;font-size:14px;font-weight:700;">
        Ver mis cargas en el portal →
      </a>
    </td>
  </tr>

  {{-- Credenciales --}}
  <tr>
    <td style="padding:0 40px 32px;text-align:center;">
      <p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.7;">
        Accede con tu correo: <strong style="color:#64748b;">{$customer->email}</strong><br>
        {$passwordHint}
      </p>
    </td>
  </tr>

  {{-- Footer --}}
  <tr>
    <td style="background:#f8fafc;padding:16px 40px;text-align:center;border-top:1px solid #e2e8f0;">
      <p style="margin:0;font-size:11px;color:#94a3b8;">
        Este correo fue enviado por <strong style="color:#64748b;">{$empresa->name}</strong>.
        Si tienes dudas, responde a este mensaje.
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

    private function fila(string $label, string $valor): string
    {
        return <<<HTML
<tr style="border-bottom:1px solid #f1f5f9;">
  <td style="padding:10px 16px;color:#64748b;white-space:nowrap;vertical-align:top;">{$label}</td>
  <td style="padding:10px 16px;color:#1e293b;font-weight:600;">{$valor}</td>
</tr>
HTML;
    }
}
