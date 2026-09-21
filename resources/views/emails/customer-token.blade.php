@php
    $marca = $empresa->name;
@endphp
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:24px;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;">
    <tr>
      <td style="padding:28px 32px 8px;">
        <p style="margin:0;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#71717a;">{{ $marca }}</p>
        <h1 style="margin:8px 0 0;font-size:20px;line-height:1.3;color:#18181b;">{{ $titulo }}</h1>
      </td>
    </tr>
    <tr>
      <td style="padding:12px 32px 0;">
        <p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#3f3f46;">
          Hola {{ $nombre }},
        </p>
        <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#3f3f46;">
          {{ $mensaje }}
        </p>
      </td>
    </tr>
    <tr>
      <td style="padding:0 32px 24px;">
        <a href="{{ $url }}"
           style="display:inline-block;padding:12px 24px;background:#18181b;color:#ffffff;text-decoration:none;border-radius:8px;font-size:15px;font-weight:700;">
          {{ $boton }}
        </a>
      </td>
    </tr>
    <tr>
      <td style="padding:0 32px 28px;">
        <p style="margin:0 0 6px;font-size:13px;line-height:1.6;color:#71717a;">
          Si el botón no funciona, copia esta dirección en tu navegador:
        </p>
        <p style="margin:0;font-size:12px;line-height:1.5;color:#a1a1aa;word-break:break-all;">{{ $url }}</p>
        @if ($caducidad)
          <p style="margin:16px 0 0;font-size:13px;line-height:1.6;color:#71717a;">{{ $caducidad }}</p>
        @endif
      </td>
    </tr>
    <tr>
      <td style="padding:16px 32px;background:#fafafa;border-top:1px solid #e4e4e7;">
        <p style="margin:0;font-size:12px;line-height:1.6;color:#a1a1aa;">
          Si no solicitaste esto, ignora este correo y no pasará nada.
        </p>
      </td>
    </tr>
  </table>
</body>
</html>
