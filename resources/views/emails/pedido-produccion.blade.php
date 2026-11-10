{{-- Avisos al cliente de un pedido que se fabrica: receptado, inicio y terminado. --}}
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:24px;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;">
    <tr>
      <td style="padding:28px 32px 8px;">
        <p style="margin:0;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#71717a;">{{ $empresa->name }}</p>
        <h1 style="margin:8px 0 0;font-size:20px;line-height:1.3;color:#18181b;">{{ $titulo }}</h1>
      </td>
    </tr>
    <tr>
      <td style="padding:12px 32px 0;">
        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#3f3f46;">Hola {{ $nombre }},</p>
        <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#3f3f46;">{{ $mensaje }}</p>
        @if ($lineas)
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;border-collapse:collapse;font-size:14px;color:#3f3f46;">
            @foreach ($lineas as $l)
              <tr>
                <td style="padding:8px 0;border-bottom:1px solid #f4f4f5;">{{ $l['producto'] }} × {{ $l['cantidad'] }}</td>
                @if ($l['lote'])
                  <td style="padding:8px 0;border-bottom:1px solid #f4f4f5;text-align:right;color:#71717a;">Lote <strong style="color:#18181b;">{{ $l['lote'] }}</strong></td>
                @endif
              </tr>
            @endforeach
          </table>
        @endif
        @if ($nota)
          <p style="margin:0 0 20px;padding:12px 14px;background:#fafafa;border-radius:8px;font-size:14px;line-height:1.6;color:#3f3f46;">{{ $nota }}</p>
        @endif
      </td>
    </tr>
    <tr>
      <td style="padding:0 32px 28px;">
        <a href="{{ $url }}" style="display:inline-block;padding:12px 24px;background:#18181b;color:#ffffff;text-decoration:none;border-radius:8px;font-size:15px;font-weight:700;">Ver mi pedido</a>
      </td>
    </tr>
    <tr>
      <td style="padding:16px 32px;background:#fafafa;border-top:1px solid #e4e4e7;">
        <p style="margin:0;font-size:12px;line-height:1.6;color:#a1a1aa;">Te escribimos para contarte cómo va tu pedido {{ $numero }}.</p>
      </td>
    </tr>
  </table>
</body>
</html>
