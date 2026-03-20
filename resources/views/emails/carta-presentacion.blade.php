<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $carta->asunto }}</title>
</head>
<body style="margin:0;padding:0;background-color:{{ $carta->color_fondo }};font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $carta->color_fondo }};padding:32px 16px;">
  <tr>
    <td align="center">
      <table width="620" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;width:100%;">

        {{-- ── HEADER ──────────────────────────────────────────────────── --}}
        <tr>
          <td style="background-color:{{ $carta->color_primario }};border-radius:10px 10px 0 0;padding:36px 40px;text-align:center;">
            @if($empresa->logo_path)
              <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path) }}"
                   alt="{{ $empresa->name }}"
                   style="max-height:64px;max-width:220px;object-fit:contain;margin-bottom:16px;display:block;margin-left:auto;margin-right:auto;">
            @endif
            <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:-0.3px;">
              {{ $empresa->name }}
            </h1>
            @if($empresa->email)
              <p style="margin:6px 0 0;color:rgba(255,255,255,0.75);font-size:13px;">{{ $empresa->email }}</p>
            @endif
          </td>
        </tr>

        {{-- ── CUERPO ───────────────────────────────────────────────────── --}}
        <tr>
          <td style="background-color:#ffffff;padding:40px 40px 32px;">

            {{-- Saludo --}}
            <p style="margin:0 0 20px;color:{{ $carta->color_texto }};font-size:15px;line-height:1.6;">
              {{ $carta->saludo }}
            </p>

            {{-- Intro --}}
            <p style="margin:0 0 32px;color:{{ $carta->color_texto }};font-size:15px;line-height:1.8;">
              {{ $carta->intro }}
            </p>

            {{-- Divisor --}}
            <hr style="border:none;border-top:1px solid #ebebeb;margin:0 0 32px;">

            {{-- Servicios --}}
            @if($servicios->count())
              <h2 style="margin:0 0 20px;color:{{ $carta->color_primario }};font-size:16px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">
                {{ $carta->servicios_titulo }}
              </h2>

              <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;">
                @foreach($servicios as $servicio)
                  <tr>
                    <td style="padding:0 0 12px;">
                      <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                          <td width="4" style="background-color:{{ $carta->color_acento }};border-radius:4px;">&nbsp;</td>
                          <td style="padding:12px 16px;background-color:#f9f9f9;border-radius:0 6px 6px 0;">
                            <p style="margin:0 0 4px;color:{{ $carta->color_primario }};font-size:14px;font-weight:700;">
                              @if($servicio->icono) {{ $servicio->icono }} @endif {{ $servicio->titulo }}
                            </p>
                            @if($servicio->descripcion)
                              <p style="margin:0;color:#666666;font-size:13px;line-height:1.6;">{{ $servicio->descripcion }}</p>
                            @endif
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                @endforeach
              </table>
            @endif

            {{-- Cierre --}}
            <p style="margin:0 0 32px;color:{{ $carta->color_texto }};font-size:15px;line-height:1.8;">
              {{ $carta->cierre }}
            </p>

            {{-- Firma --}}
            <table cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="border-left:3px solid {{ $carta->color_acento }};padding-left:14px;">
                  <p style="margin:0;color:{{ $carta->color_primario }};font-size:15px;font-weight:700;">{{ $carta->firma_nombre }}</p>
                  @if($carta->firma_cargo)
                    <p style="margin:3px 0 0;color:#888888;font-size:13px;">{{ $carta->firma_cargo }}</p>
                  @endif
                  <p style="margin:3px 0 0;color:#888888;font-size:13px;">{{ $empresa->name }}</p>
                </td>
              </tr>
            </table>

          </td>
        </tr>

        {{-- ── CONTACTO ─────────────────────────────────────────────────── --}}
        @if($contacto)
          <tr>
            <td style="background-color:{{ $carta->color_primario }};padding:24px 40px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  @if($contacto->telefono)
                    <td style="color:rgba(255,255,255,0.85);font-size:12px;padding-right:24px;">
                      <span style="color:rgba(255,255,255,0.5);display:block;font-size:10px;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;">Teléfono</span>
                      {{ $contacto->telefono }}
                    </td>
                  @endif
                  @if($contacto->email)
                    <td style="color:rgba(255,255,255,0.85);font-size:12px;padding-right:24px;">
                      <span style="color:rgba(255,255,255,0.5);display:block;font-size:10px;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;">Email</span>
                      {{ $contacto->email }}
                    </td>
                  @endif
                  @if($contacto->whatsapp)
                    <td style="color:rgba(255,255,255,0.85);font-size:12px;">
                      <span style="color:rgba(255,255,255,0.5);display:block;font-size:10px;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:3px;">WhatsApp</span>
                      {{ $contacto->whatsapp }}
                    </td>
                  @endif
                </tr>
                @if($contacto->direccion)
                  <tr>
                    <td colspan="3" style="padding-top:12px;color:rgba(255,255,255,0.6);font-size:11px;">
                      {{ $contacto->direccion }}
                    </td>
                  </tr>
                @endif
              </table>
            </td>
          </tr>
        @endif

        {{-- ── FOOTER ───────────────────────────────────────────────────── --}}
        <tr>
          <td style="background-color:#1a1a1a;border-radius:0 0 10px 10px;padding:16px 40px;text-align:center;">
            <p style="margin:0;color:rgba(255,255,255,0.35);font-size:11px;">
              Este correo fue enviado por <strong style="color:rgba(255,255,255,0.55);">{{ $empresa->name }}</strong> a través de Mashaec ERP.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
