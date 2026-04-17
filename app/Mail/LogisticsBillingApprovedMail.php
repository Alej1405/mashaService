<?php

namespace App\Mail;

use App\Models\Empresa;
use App\Models\LogisticsBillingRequest;
use App\Models\StoreCustomer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class LogisticsBillingApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly LogisticsBillingRequest $billing,
        public readonly StoreCustomer           $customer,
        public readonly Empresa                 $empresa,
        public readonly string                  $observacion = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[' . $this->empresa->name . '] Los valores de tu carga han sido aprobados — ' . $this->billing->numero_nota_venta,
        );
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->buildHtml());
    }

    public function buildHtml(): string
    {
        $empresa  = $this->empresa;
        $billing  = $this->billing;
        $customer = $this->customer;
        $package  = $billing->package;

        $empName = e($empresa->name);
        $numero  = e($billing->numero_nota_venta);
        $total   = '$' . number_format($billing->total, 2);
        $hola    = e($customer->nombre_completo);
        $obs     = $this->observacion ? e($this->observacion) : null;

        $logoHtml = '';
        if ($empresa->logo_path) {
            $logoUrl  = Storage::disk('public')->url($empresa->logo_path);
            $logoHtml = "<img src='{$logoUrl}' alt='" . e($empresa->name) . "' style='max-height:44px;max-width:160px;object-fit:contain;display:block;margin:0 auto 12px;'>";
        }

        // Líneas de la nota de venta
        $itemsHtml = '';
        foreach ($billing->items as $item) {
            $desc  = e($item['descripcion']);
            $price = '$' . number_format($item['total'], 2);
            $ivaPct = $item['iva_pct'] . '%';
            $itemsHtml .= <<<HTML
<tr style="border-bottom:1px solid #f1f5f9;">
  <td style="padding:9px 14px;font-size:12px;color:#374151;">{$desc}</td>
  <td style="padding:9px 14px;font-size:12px;color:#374151;text-align:center;">{$ivaPct}</td>
  <td style="padding:9px 14px;font-size:12px;font-weight:600;color:#111827;text-align:right;">{$price}</td>
</tr>
HTML;
        }

        $sub0  = '$' . number_format($billing->subtotal_0,  2);
        $sub15 = '$' . number_format($billing->subtotal_15, 2);
        $iva   = '$' . number_format($billing->iva,         2);

        $billingNombre  = e($billing->billing_nombre ?? '—');
        $billingRucHtml = $billing->billing_ruc
            ? "<p style='margin:2px 0 0;font-size:12px;color:#64748b;font-family:monospace;'>RUC / CI: " . e($billing->billing_ruc) . "</p>"
            : '';

        $obsHtml = $obs ? <<<HTML
  <tr>
    <td style="padding:0 40px 24px;">
      <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:16px 20px;">
        <p style="margin:0 0 6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#166534;">
          Observación del equipo
        </p>
        <p style="margin:0;font-size:13px;color:#166534;line-height:1.6;">{$obs}</p>
      </div>
    </td>
  </tr>
HTML : '';

        $portalUrl = url('/tienda/' . $empresa->slug);

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
      <p style="margin:0;color:rgba(255,255,255,.45);font-size:11px;letter-spacing:1.5px;text-transform:uppercase;">{$empName}</p>
    </td>
  </tr>

  <tr>
    <td style="padding:28px 40px 4px;text-align:center;">
      <span style="display:inline-block;background:#dcfce718;color:#16a34a;border:1px solid #86efac;
                   border-radius:999px;padding:6px 18px;font-size:13px;font-weight:700;">
        ✓ Valores aprobados
      </span>
    </td>
  </tr>

  <tr>
    <td style="padding:20px 40px 8px;">
      <p style="margin:0;font-size:17px;font-weight:700;color:#1e293b;">Hola, {$hola}</p>
      <p style="margin:8px 0 0;font-size:14px;color:#475569;line-height:1.6;">
        Los valores de tu carga han sido revisados y aprobados por nuestro equipo.
        A continuación encontrarás el detalle de la nota de venta <strong>{$numero}</strong>.
      </p>
    </td>
  </tr>

  {{-- Nota de venta compacta --}}
  <tr>
    <td style="padding:16px 40px;">
      <div style="border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
        <div style="background:#1e293b;padding:12px 18px;display:flex;justify-content:space-between;align-items:center;">
          <span style="color:#f1f5f9;font-weight:700;font-size:13px;">{$empName}</span>
          <div style="text-align:right;">
            <span style="color:#f97316;font-weight:800;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Nota de Venta</span><br>
            <span style="color:#f1f5f9;font-family:monospace;font-size:12px;">{$numero}</span>
          </div>
        </div>
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
          <thead>
            <tr style="background:#f8fafc;">
              <th style="padding:8px 14px;font-size:10px;text-transform:uppercase;color:#6b7280;text-align:left;">Descripción</th>
              <th style="padding:8px 14px;font-size:10px;text-transform:uppercase;color:#6b7280;text-align:center;">IVA</th>
              <th style="padding:8px 14px;font-size:10px;text-transform:uppercase;color:#6b7280;text-align:right;">Total</th>
            </tr>
          </thead>
          <tbody>{$itemsHtml}</tbody>
        </table>
        <table width="100%" cellpadding="0" cellspacing="0" border="0"
               style="border-top:2px solid #e5e7eb;background:#f8fafc;padding:12px 18px;">
          <tr>
            <td style="width:55%;"></td>
            <td style="padding:4px 14px;">
              <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td style="font-size:11px;color:#6b7280;padding:2px 0;">SUBTOTAL 0%</td>
                  <td style="font-size:11px;color:#374151;text-align:right;">{$sub0}</td>
                </tr>
                <tr>
                  <td style="font-size:11px;color:#6b7280;padding:2px 0;">SUBTOTAL 15%</td>
                  <td style="font-size:11px;color:#374151;text-align:right;">{$sub15}</td>
                </tr>
                <tr>
                  <td style="font-size:11px;color:#6b7280;padding:2px 0;">IVA 15%</td>
                  <td style="font-size:11px;color:#374151;text-align:right;">{$iva}</td>
                </tr>
                <tr style="border-top:1px solid #d1d5db;">
                  <td style="font-size:13px;font-weight:800;color:#111827;padding-top:6px;">VALOR TOTAL</td>
                  <td style="font-size:15px;font-weight:800;color:#b45309;text-align:right;padding-top:6px;">{$total}</td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </div>
    </td>
  </tr>

  {{-- Datos de facturación --}}
  <tr>
    <td style="padding:0 40px 20px;">
      <div style="background:#f8fafc;border-radius:8px;padding:14px 18px;">
        <p style="margin:0 0 8px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;">
          Factura a emitir a nombre de
        </p>
        <p style="margin:0;font-size:14px;font-weight:700;color:#1e293b;">{$billingNombre}</p>
        {$billingRucHtml}
      </div>
    </td>
  </tr>

  {$obsHtml}

  <tr>
    <td style="padding:4px 40px 28px;text-align:center;">
      <a href="{$portalUrl}" target="_blank"
         style="display:inline-block;background:#4f46e5;color:#ffffff;text-decoration:none;
                padding:12px 32px;border-radius:8px;font-size:14px;font-weight:700;">
        Ver mis cargas en el portal →
      </a>
    </td>
  </tr>

  <tr>
    <td style="background:#f8fafc;padding:16px 40px;text-align:center;border-top:1px solid #e2e8f0;">
      <p style="margin:0;font-size:11px;color:#94a3b8;">
        Este correo fue enviado por <strong style="color:#64748b;">{$empName}</strong>.
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
}
