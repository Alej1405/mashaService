<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>{{ $carta->asunto }}</title>
</head>
<body style="margin:0;padding:0;background-color:{{ $carta->color_fondo }};font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:{{ $carta->color_fondo }};padding:40px 16px;">
<tr><td align="center">
<table width="640" cellpadding="0" cellspacing="0" border="0" style="max-width:640px;width:100%;">

  {{-- ── HEADER con degradé diagonal ───────────────────────────────────── --}}
  <tr>
    <td style="background:linear-gradient(135deg,{{ $carta->color_primario }} 0%,{{ $carta->color_acento }} 100%);border-radius:12px 12px 0 0;padding:48px 48px 56px;position:relative;overflow:hidden;">

      {{-- Círculo decorativo fondo --}}
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="vertical-align:middle;">
            @if($empresa->logo_path)
              <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path) }}"
                   alt="{{ $empresa->name }}"
                   style="max-height:52px;max-width:160px;object-fit:contain;display:block;margin-bottom:20px;filter:brightness(0) invert(1);opacity:0.9;">
            @endif
            <h1 style="margin:0 0 6px;color:#ffffff;font-size:28px;font-weight:800;letter-spacing:-1px;line-height:1.1;">
              {{ $empresa->name }}
            </h1>
            <p style="margin:0;color:rgba(255,255,255,0.7);font-size:13px;letter-spacing:0.5px;">
              Carta de Presentación &nbsp;/&nbsp; {{ now()->format('Y') }}
            </p>
          </td>
          <td width="120" style="text-align:right;vertical-align:middle;">
            {{-- Elemento geométrico decorativo --}}
            <table cellpadding="0" cellspacing="0" border="0" style="margin-left:auto;">
              <tr>
                <td style="width:80px;height:80px;border:3px solid rgba(255,255,255,0.25);transform:rotate(45deg);display:block;"></td>
              </tr>
              <tr>
                <td style="width:50px;height:50px;background:rgba(255,255,255,0.15);transform:rotate(45deg);margin:-20px auto 0;display:block;"></td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

    </td>
  </tr>

  {{-- Franja de transición --}}
  <tr>
    <td style="background:linear-gradient(135deg,{{ $carta->color_primario }} 0%,{{ $carta->color_acento }} 100%);padding:0 48px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="background-color:#ffffff;border-radius:8px 8px 0 0;height:20px;"></td>
        </tr>
      </table>
    </td>
  </tr>

  {{-- ── CUERPO ──────────────────────────────────────────────────────────── --}}
  <tr>
    <td style="background-color:#ffffff;padding:36px 48px 32px;border-left:1px solid #f0f0f0;border-right:1px solid #f0f0f0;">

      {{-- Saludo con línea decorativa --}}
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:24px;">
        <tr>
          <td width="32" style="vertical-align:middle;padding-right:12px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M20 4H4C2.9 4 2 4.9 2 6V18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V6C22 4.9 21.1 4 20 4Z" stroke="{{ $carta->color_primario }}" stroke-width="1.5" fill="none"/>
              <path d="M22 6L12 13L2 6" stroke="{{ $carta->color_acento }}" stroke-width="1.5" fill="none"/>
            </svg>
          </td>
          <td>
            <p style="margin:0;color:{{ $carta->color_primario }};font-size:15px;font-weight:600;">{{ $carta->saludo }}</p>
          </td>
        </tr>
      </table>

      <p style="margin:0 0 36px;color:{{ $carta->color_texto }};font-size:14px;line-height:1.9;">
        {{ $carta->intro }}
      </p>

      {{-- Divisor --}}
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;">
        <tr>
          <td width="40" style="height:3px;background:linear-gradient(90deg,{{ $carta->color_primario }},{{ $carta->color_acento }});border-radius:2px;"></td>
          <td style="height:3px;background-color:#f0f0f0;"></td>
        </tr>
      </table>

      {{-- Servicios --}}
      @if($servicios->count())
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:36px;">
          <tr>
            <td style="padding-bottom:20px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td>
                    <p style="margin:0;color:{{ $carta->color_primario }};font-size:11px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;">
                      {{ $carta->servicios_titulo }}
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          @foreach($servicios as $i => $servicio)
          <tr>
            <td style="padding-bottom:12px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  {{-- Número grande --}}
                  <td width="56" style="vertical-align:top;padding-right:16px;padding-top:2px;">
                    <p style="margin:0;font-size:28px;font-weight:800;line-height:1;background:linear-gradient(135deg,{{ $carta->color_primario }},{{ $carta->color_acento }});-webkit-background-clip:text;color:{{ $carta->color_primario }};opacity:0.2;font-family:Georgia,serif;">
                      {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                    </p>
                  </td>
                  {{-- Contenido --}}
                  <td style="border-left:2px solid #f0f0f0;padding-left:16px;vertical-align:top;">
                    <p style="margin:0 0 4px;color:{{ $carta->color_primario }};font-size:14px;font-weight:700;">{{ $servicio->titulo }}</p>
                    @if($servicio->descripcion)
                      <p style="margin:0;color:#888888;font-size:12px;line-height:1.7;">{{ $servicio->descripcion }}</p>
                    @endif
                  </td>
                  {{-- Indicador visual --}}
                  <td width="8" style="vertical-align:stretch;padding-left:16px;">
                    <div style="width:4px;height:100%;min-height:24px;background:linear-gradient(180deg,{{ $carta->color_acento }}40,{{ $carta->color_primario }}40);border-radius:2px;"></div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          @endforeach
        </table>
      @endif

      {{-- Divisor --}}
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;">
        <tr>
          <td style="height:1px;background:linear-gradient(90deg,transparent,#e0e0e0,transparent);"></td>
        </tr>
      </table>

      {{-- ── Equipo ──────────────────────────────────────────────────────── --}}
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:36px;">
        <tr>
          <td style="padding-bottom:16px;">
            <p style="margin:0;color:{{ $carta->color_primario }};font-size:11px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;">Nuestro equipo</p>
          </td>
        </tr>
        @if($equipo->count())
          @foreach($equipo as $i => $miembro)
          <tr>
            <td style="padding-bottom:12px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td width="56" style="vertical-align:top;padding-right:16px;padding-top:2px;">
                    <p style="margin:0;font-size:28px;font-weight:800;line-height:1;color:{{ $carta->color_primario }};opacity:0.15;font-family:Georgia,serif;">
                      {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                    </p>
                  </td>
                  <td style="border-left:2px solid #f0f0f0;padding-left:16px;vertical-align:top;">
                    <p style="margin:0 0 2px;color:{{ $carta->color_primario }};font-size:14px;font-weight:700;">{{ $miembro->nombre }}</p>
                    @if($miembro->cargo)
                      <p style="margin:0 0 3px;color:{{ $carta->color_acento }};font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">{{ $miembro->cargo }}</p>
                    @endif
                    @if($miembro->bio)
                      <p style="margin:0;color:#888888;font-size:12px;line-height:1.6;">{{ $miembro->bio }}</p>
                    @endif
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          @endforeach
        @else
          <tr>
            <td style="padding:14px 18px;background-color:#fffbf0;border:1px dashed {{ $carta->color_acento }}66;border-radius:8px;text-align:center;">
              <p style="margin:0;color:{{ $carta->color_acento }};font-size:12px;">
                &#9888; Completa la información del equipo en el panel CMS → <strong>Equipo</strong>
              </p>
            </td>
          </tr>
        @endif
      </table>

      {{-- Cierre --}}
      <p style="margin:0 0 36px;color:{{ $carta->color_texto }};font-size:14px;line-height:1.9;">
        {{ $carta->cierre }}
      </p>

      {{-- Firma --}}
      <table cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="background:linear-gradient(135deg,{{ $carta->color_primario }}0d,{{ $carta->color_acento }}0d);border:1px solid {{ $carta->color_acento }}33;border-radius:8px;padding:16px 24px;">
            <table cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td width="36" style="vertical-align:middle;padding-right:14px;">
                  <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="16" cy="16" r="15" stroke="{{ $carta->color_primario }}" stroke-width="1.5" fill="none"/>
                    <circle cx="16" cy="12" r="4" stroke="{{ $carta->color_acento }}" stroke-width="1.5" fill="none"/>
                    <path d="M8 24C8 20 11.6 18 16 18C20.4 18 24 20 24 24" stroke="{{ $carta->color_primario }}" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                  </svg>
                </td>
                <td>
                  <p style="margin:0 0 2px;color:{{ $carta->color_primario }};font-size:15px;font-weight:700;">{{ $carta->firma_nombre }}</p>
                  @if($carta->firma_cargo)
                    <p style="margin:0 0 1px;color:{{ $carta->color_acento }};font-size:11px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">{{ $carta->firma_cargo }}</p>
                  @endif
                  <p style="margin:0;color:#aaaaaa;font-size:11px;">{{ $empresa->name }}</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

    </td>
  </tr>

  {{-- ── CONTACTO ────────────────────────────────────────────────────────── --}}
  <tr>
    <td style="background-color:#f8f9fa;border:1px solid #f0f0f0;border-top:none;padding:24px 48px;">
      @if(! $contacto)
      <p style="margin:0;color:{{ $carta->color_acento }};font-size:12px;text-align:center;">
        &#9888; Completa la información de contacto en el panel CMS → <strong>Contacto</strong>
      </p>
      @else
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          @if($contacto->telefono)
          <td style="vertical-align:top;padding-right:24px;">
            <table cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td width="24" style="vertical-align:middle;padding-right:8px;">
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M3 2H6L7.5 5.5L5.5 6.5C6.3 8.3 7.7 9.7 9.5 10.5L10.5 8.5L14 10V13C14 13.6 13.6 14 13 14C7 14 2 9 2 3C2 2.4 2.4 2 3 2Z" stroke="{{ $carta->color_acento }}" stroke-width="1.3" fill="none" stroke-linejoin="round"/>
                  </svg>
                </td>
                <td>
                  <p style="margin:0 0 1px;color:#aaaaaa;font-size:9px;letter-spacing:1px;text-transform:uppercase;">Teléfono</p>
                  <p style="margin:0;color:{{ $carta->color_texto }};font-size:12px;font-weight:600;">{{ $contacto->telefono }}</p>
                </td>
              </tr>
            </table>
          </td>
          @endif
          @if($contacto->email)
          <td style="vertical-align:top;padding-right:24px;">
            <table cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td width="24" style="vertical-align:middle;padding-right:8px;">
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <rect x="1" y="3" width="14" height="10" rx="2" stroke="{{ $carta->color_acento }}" stroke-width="1.3" fill="none"/>
                    <path d="M1 5L8 9L15 5" stroke="{{ $carta->color_acento }}" stroke-width="1.3" fill="none"/>
                  </svg>
                </td>
                <td>
                  <p style="margin:0 0 1px;color:#aaaaaa;font-size:9px;letter-spacing:1px;text-transform:uppercase;">Email</p>
                  <p style="margin:0;color:{{ $carta->color_texto }};font-size:12px;font-weight:600;">{{ $contacto->email }}</p>
                </td>
              </tr>
            </table>
          </td>
          @endif
          @if($contacto->whatsapp)
          <td style="vertical-align:top;">
            <table cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td width="24" style="vertical-align:middle;padding-right:8px;">
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <circle cx="8" cy="8" r="7" stroke="{{ $carta->color_acento }}" stroke-width="1.3" fill="none"/>
                    <path d="M11 9.3C11 9.5 10.9 9.7 10.8 9.8C10.6 10.1 10.3 10.3 10 10.4C9.8 10.4 9.5 10.5 9.2 10.3C9 10.2 8.7 10.1 8.4 9.9C7.7 9.5 7.1 9 6.6 8.4C6.3 8.1 6.1 7.8 5.9 7.5C5.8 7.3 5.7 7.1 5.6 6.9C5.5 6.6 5.6 6.3 5.7 6.1C5.8 5.9 6 5.8 6.2 5.7H6.5C6.6 5.7 6.7 5.8 6.8 6L7.2 6.9C7.3 7.1 7.2 7.3 7.1 7.4L6.9 7.6C7.1 7.9 7.4 8.2 7.7 8.4C8 8.6 8.3 8.8 8.7 8.9L8.9 8.7C9 8.6 9.2 8.5 9.4 8.6L10.3 9C10.5 9.1 10.6 9.2 10.6 9.3H11Z" stroke="{{ $carta->color_acento }}" stroke-width="0.8" fill="{{ $carta->color_acento }}"/>
                  </svg>
                </td>
                <td>
                  <p style="margin:0 0 1px;color:#aaaaaa;font-size:9px;letter-spacing:1px;text-transform:uppercase;">WhatsApp</p>
                  <p style="margin:0;color:{{ $carta->color_texto }};font-size:12px;font-weight:600;">{{ $contacto->whatsapp }}</p>
                </td>
              </tr>
            </table>
          </td>
          @endif
        </tr>
        @if($contacto->direccion)
        <tr>
          <td colspan="3" style="padding-top:16px;">
            <p style="margin:0;color:#aaaaaa;font-size:11px;">{{ $contacto->direccion }}</p>
          </td>
        </tr>
        @endif
      </table>
      @endif
    </td>
  </tr>

  {{-- ── FOOTER ──────────────────────────────────────────────────────────── --}}
  <tr>
    <td style="background:linear-gradient(135deg,{{ $carta->color_primario }} 0%,{{ $carta->color_acento }} 100%);border-radius:0 0 12px 12px;padding:14px 48px;text-align:center;">
      <p style="margin:0;color:rgba(255,255,255,0.6);font-size:10px;letter-spacing:0.5px;">
        Generado por <strong style="color:rgba(255,255,255,0.85);">Mashaec ERP</strong> &nbsp;·&nbsp; {{ $empresa->name }}
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
