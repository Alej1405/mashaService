<?php

namespace App\Mail;

use App\Models\BankAccount;
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
        public readonly bool             $solicitarPago = false,
    ) {}

    public function envelope(): Envelope
    {
        $info  = LogisticsPackage::ESTADOS[$this->package->estado] ?? [];
        $label = $info['label'] ?? $this->package->estado;

        if ($this->package->estado_secundario) {
            $sec = LogisticsPackage::ESTADOS_SECUNDARIOS[$this->package->estado][$this->package->estado_secundario] ?? null;
            if ($sec) {
                $label .= ' › ' . $sec['label'];
            }
        }

        $asunto = $this->solicitarPago
            ? '[' . $this->empresa->name . '] Tu carga está lista — Solicitud de pago'
            : '[' . $this->empresa->name . '] Tu carga: ' . $label;

        return new Envelope(subject: $asunto);
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

        $estadoInfo  = LogisticsPackage::ESTADOS[$package->estado] ?? [];
        $estadoLabel = $estadoInfo['label'] ?? $package->estado;
        $estadoColor = $estadoInfo['color'] ?? '#6366f1';

        // Estado secundario
        $secInfo  = $package->estado_secundario
            ? (LogisticsPackage::ESTADOS_SECUNDARIOS[$package->estado][$package->estado_secundario] ?? null)
            : null;
        $secLabel    = $secInfo['label'] ?? null;
        $secColor    = $secInfo['color'] ?? '#6366f1';
        $secBadgeHtml = $secLabel
            ? "<br><span style='display:inline-block;margin-top:6px;background:{$secColor}18;color:{$secColor};border:1px solid {$secColor}44;border-radius:999px;padding:3px 12px;font-size:11px;font-weight:600;'>{$secLabel}</span>"
            : '';

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

        // Sección de solicitud de pago
        $pagoHtml = '';
        if ($this->solicitarPago && $package->monto_cobro) {
            $monto = '$' . number_format($package->monto_cobro, 2);

            // Cuentas bancarias de la empresa
            $cuentas = BankAccount::withoutGlobalScopes()
                ->where('empresa_id', $empresa->id)
                ->where('activo', true)
                ->with('bank')
                ->get();

            $cuentasHtml = '';
            foreach ($cuentas as $cuenta) {
                $banco    = e($cuenta->bank->nombre ?? '—');
                $tipo     = $cuenta->tipo_cuenta === 'ahorros' ? 'Ahorros' : 'Corriente';
                $numero   = e($cuenta->numero_cuenta);
                $titular  = e($cuenta->nombre_titular);
                $cuentasHtml .= <<<HTML

        <table width="100%" cellpadding="0" cellspacing="6" border="0"
               style="background:#fff;border:1px solid #fde68a;border-radius:8px;padding:10px 14px;margin-top:10px;font-size:12px;">
          <tr>
            <td style="color:#92400e;width:50%;vertical-align:top;">
              <span style="display:block;font-size:10px;color:#a16207;text-transform:uppercase;letter-spacing:.5px;">Banco</span>
              <strong>{$banco}</strong>
            </td>
            <td style="color:#92400e;vertical-align:top;">
              <span style="display:block;font-size:10px;color:#a16207;text-transform:uppercase;letter-spacing:.5px;">Tipo</span>
              <strong>{$tipo}</strong>
            </td>
          </tr>
          <tr>
            <td style="color:#92400e;vertical-align:top;padding-top:6px;">
              <span style="display:block;font-size:10px;color:#a16207;text-transform:uppercase;letter-spacing:.5px;">N.° de cuenta</span>
              <strong style="font-family:monospace;">{$numero}</strong>
            </td>
            <td style="color:#92400e;vertical-align:top;padding-top:6px;">
              <span style="display:block;font-size:10px;color:#a16207;text-transform:uppercase;letter-spacing:.5px;">Titular</span>
              <strong>{$titular}</strong>
            </td>
          </tr>
        </table>
HTML;
            }

            $cuentasSeccionHtml = $cuentasHtml
                ? "<p style='margin:16px 0 6px;font-size:12px;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.5px;'>Datos para transferencia</p>{$cuentasHtml}"
                : '';

            $pagoHtml = <<<HTML
  <tr>
    <td style="padding:0 40px 24px;">
      <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:20px 24px;">
        <p style="margin:0 0 6px;font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#92400e;">
          Solicitud de Pago
        </p>
        <p style="margin:0 0 12px;font-size:14px;color:#78350f;line-height:1.5;">
          Tu carga ha finalizado el proceso en aduana y está lista para ser despachada.
          Para proceder con la entrega, realiza el pago de los servicios de importación.
        </p>
        <div style="display:flex;align-items:center;justify-content:space-between;
                    background:#fff;border-radius:8px;padding:12px 16px;border:1px solid #fde68a;">
          <span style="font-size:13px;color:#92400e;font-weight:600;">Monto a pagar</span>
          <span style="font-size:22px;font-weight:800;color:#b45309;">{$monto}</span>
        </div>
        {$cuentasSeccionHtml}
        <p style="margin:14px 0 0;font-size:12px;color:#a16207;line-height:1.5;">
          Una vez realizado el pago, comunícate con nosotros para coordinar la entrega de tu carga.
        </p>
      </div>
    </td>
  </tr>
HTML;
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

  <tr>
    <td style="background:#1e293b;padding:32px 40px 24px;text-align:center;">
      {$logoHtml}
      <p style="margin:0;color:rgba(255,255,255,.45);font-size:11px;letter-spacing:1.5px;text-transform:uppercase;">
        {$empresa->name}
      </p>
    </td>
  </tr>

  <tr>
    <td style="padding:28px 40px 4px;text-align:center;">
      <span style="display:inline-block;background:{$estadoColor}18;color:{$estadoColor};border:1px solid {$estadoColor}44;
                   border-radius:999px;padding:6px 18px;font-size:13px;font-weight:700;">
        {$estadoLabel}
      </span>
      {$secBadgeHtml}
    </td>
  </tr>

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

  <tr>
    <td style="padding:20px 40px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;font-size:13px;">
        {$detalles}
      </table>
    </td>
  </tr>

  {$embarqueHtml}

  {$pagoHtml}

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
    <td style="padding:0 40px 32px;text-align:center;">
      <p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.7;">
        Accede con tu correo: <strong style="color:#64748b;">{$customer->email}</strong><br>
        {$passwordHint}
      </p>
    </td>
  </tr>

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
