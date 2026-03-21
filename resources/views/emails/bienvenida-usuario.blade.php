<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Bienvenido a {{ $empresa->name }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f6f9;padding:40px 16px;">
<tr><td align="center">
<table width="580" cellpadding="0" cellspacing="0" border="0" style="max-width:580px;width:100%;">

  {{-- HEADER --}}
  <tr>
    <td style="background-color:#1e293b;border-radius:12px 12px 0 0;padding:32px 48px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td>
            @if($empresa->logo_path)
              <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path) }}"
                   alt="{{ $empresa->name }}"
                   style="max-height:48px;max-width:160px;object-fit:contain;display:block;margin-bottom:12px;">
            @endif
            <p style="margin:0;color:rgba(255,255,255,0.5);font-size:11px;letter-spacing:1.5px;text-transform:uppercase;">
              {{ $empresa->name }}
            </p>
          </td>
          <td width="56" style="text-align:right;vertical-align:middle;">
            <div style="width:48px;height:48px;background-color:#3b82f6;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;">
              <svg width="24" height="24" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z" fill="white"/>
              </svg>
            </div>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  {{-- TITULO --}}
  <tr>
    <td style="background-color:#ffffff;padding:40px 48px 24px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;">
      <h1 style="margin:0 0 8px;color:#1e293b;font-size:22px;font-weight:700;line-height:1.3;">
        ¡Bienvenido/a, {{ $usuario->name }}!
      </h1>
      <p style="margin:0;color:#64748b;font-size:14px;line-height:1.6;">
        Tu cuenta ha sido creada exitosamente en <strong>{{ $empresa->name }}</strong>.
        A continuación encontrarás tus datos de acceso.
      </p>
    </td>
  </tr>

  {{-- CREDENCIALES --}}
  <tr>
    <td style="background-color:#ffffff;padding:0 48px 32px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0"
             style="background-color:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
        <tr>
          <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0;">
            <p style="margin:0 0 4px;color:#94a3b8;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;">
              Correo electrónico
            </p>
            <p style="margin:0;color:#1e293b;font-size:15px;font-weight:600;">
              {{ $usuario->email }}
            </p>
          </td>
        </tr>
        <tr>
          <td style="padding:20px 24px;border-bottom:1px solid #e2e8f0;">
            <p style="margin:0 0 4px;color:#94a3b8;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;">
              Contraseña temporal
            </p>
            <p style="margin:0;color:#1e293b;font-size:15px;font-weight:600;font-family:monospace;letter-spacing:1px;">
              {{ $password }}
            </p>
          </td>
        </tr>
        <tr>
          <td style="padding:20px 24px;">
            <p style="margin:0 0 4px;color:#94a3b8;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;">
              Rol asignado
            </p>
            <p style="margin:0;color:#1e293b;font-size:15px;font-weight:600;">
              {{ $rolLabel }}
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  {{-- CTA --}}
  <tr>
    <td style="background-color:#ffffff;padding:0 48px 40px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;text-align:center;">
      <a href="{{ $loginUrl }}"
         style="display:inline-block;background-color:#3b82f6;color:#ffffff;font-size:14px;font-weight:700;
                padding:14px 36px;border-radius:8px;text-decoration:none;letter-spacing:0.3px;">
        Ingresar al sistema
      </a>
      <p style="margin:16px 0 0;color:#94a3b8;font-size:12px;">
        Por seguridad, te recomendamos cambiar tu contraseña en el primer acceso.
      </p>
    </td>
  </tr>

  {{-- FOOTER --}}
  <tr>
    <td style="background-color:#1e293b;border-radius:0 0 12px 12px;padding:20px 48px;text-align:center;">
      <p style="margin:0;color:rgba(255,255,255,0.3);font-size:11px;">
        Este correo fue generado automáticamente por <strong style="color:rgba(255,255,255,0.5);">{{ $empresa->name }}</strong>
        &nbsp;·&nbsp; No respondas a este mensaje.
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
