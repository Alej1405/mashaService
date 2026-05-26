<?php

namespace App\Mail;

use App\Models\Empresa;
use App\Models\ProductionPlan;
use Illuminate\Mail\Mailable;

class NuevaPlanificacionMail extends Mailable
{
    public function __construct(
        public readonly ProductionPlan $plan,
        public readonly string         $nombreDisenio,
        public readonly string         $tipoDisenio,
        public readonly Empresa        $empresa,
    ) {}

    public function envelope(): \Illuminate\Mail\Mailables\Envelope
    {
        return new \Illuminate\Mail\Mailables\Envelope(
            subject: '[' . $this->empresa->name . '] Nueva planificación: ' . $this->nombreDisenio
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
        $plan       = $this->plan;
        $empresa    = $this->empresa;
        $empresaNom = e($empresa->name);
        $nombre     = e($this->nombreDisenio);
        $tipo       = $this->tipoDisenio === 'producto' ? 'Producto' : 'Servicio';

        $tipoProd = $plan->tipo_produccion === 'unica' ? 'Producción única' : 'En parciales';
        $parciales = $plan->num_parciales ? "({$plan->num_parciales} parciales)" : '';
        $inicio    = $plan->fecha_inicio->format('d/m/Y');
        $fin       = $plan->fecha_fin->format('d/m/Y');

        $logoHtml = '';
        if ($empresa->logo_path) {
            $logoUrl  = \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path);
            $logoHtml = "<img src='{$logoUrl}' alt='{$empresaNom}' style='max-height:48px;max-width:170px;object-fit:contain;display:block;margin:0 auto 12px;'>";
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
  <tr>
    <td style="background:#1e293b;padding:32px 40px 24px;text-align:center;">
      {$logoHtml}
      <p style="margin:0;color:rgba(255,255,255,.45);font-size:11px;letter-spacing:1.5px;text-transform:uppercase;">{$empresaNom}</p>
    </td>
  </tr>
  <tr>
    <td style="padding:36px 40px 0;text-align:center;">
      <div style="display:inline-block;background:#6366f118;border:1px solid #6366f144;border-radius:999px;padding:6px 20px;font-size:13px;font-weight:700;color:#6366f1;margin-bottom:20px;">
        Nueva planificación de producción
      </div>
      <p style="margin:0;font-size:22px;font-weight:800;color:#1e293b;line-height:1.3;">{$nombre}</p>
      <p style="margin:10px 0 0;font-size:14px;color:#64748b;">{$tipo}</p>
    </td>
  </tr>
  <tr>
    <td style="padding:28px 40px;">
      <div style="background:#f8fafc;border-radius:10px;padding:20px 24px;border:1px solid #e2e8f0;">
        <p style="margin:0 0 12px;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#6366f1;">
          Detalle de planificación
        </p>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:14px;">
          <tr>
            <td style="padding:5px 0;color:#64748b;width:160px;">Tipo de producción:</td>
            <td style="padding:5px 0;color:#1e293b;font-weight:700;">{$tipoProd} {$parciales}</td>
          </tr>
          <tr>
            <td style="padding:5px 0;color:#64748b;">Fecha de inicio:</td>
            <td style="padding:5px 0;color:#1e293b;font-weight:700;">{$inicio}</td>
          </tr>
          <tr>
            <td style="padding:5px 0;color:#64748b;">Fecha de fin:</td>
            <td style="padding:5px 0;color:#1e293b;font-weight:700;">{$fin}</td>
          </tr>
        </table>
      </div>
    </td>
  </tr>
  <tr>
    <td style="background:#f8fafc;padding:16px 40px;text-align:center;border-top:1px solid #e2e8f0;">
      <p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.7;">
        Notificación automática de <strong style="color:#64748b;">{$empresaNom}</strong> · Mashaec ERP
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
}
