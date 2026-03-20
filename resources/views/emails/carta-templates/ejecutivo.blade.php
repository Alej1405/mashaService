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

  {{-- ── HEADER ─────────────────────────────────────────────────────────── --}}
  <tr>
    <td style="background:linear-gradient(160deg,{{ $carta->color_primario }} 0%,{{ $carta->color_primario }}cc 100%);border-radius:12px 12px 0 0;padding:0;">

      {{-- Franja superior de acento --}}
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td width="60%" style="background-color:{{ $carta->color_acento }};height:5px;border-radius:12px 0 0 0;"></td>
          <td width="40%" style="background-color:{{ $carta->color_primario }};height:5px;border-radius:0 12px 0 0;"></td>
        </tr>
      </table>

      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="padding:36px 48px 32px;">
        <tr>
          <td style="vertical-align:middle;">
            @if($empresa->logo_path)
              <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path) }}"
                   alt="{{ $empresa->name }}"
                   style="max-height:56px;max-width:180px;object-fit:contain;display:block;margin-bottom:16px;">
            @endif
            <h1 style="margin:0 0 4px;color:#ffffff;font-size:24px;font-weight:700;letter-spacing:-0.5px;line-height:1.2;">
              {{ $empresa->name }}
            </h1>
            @if($empresa->email)
              <p style="margin:0;color:rgba(255,255,255,0.55);font-size:12px;letter-spacing:0.3px;">{{ $empresa->email }}</p>
            @endif
          </td>
          <td width="80" style="vertical-align:bottom;text-align:right;">
            {{-- Marca de agua geométrica --}}
            <div style="width:64px;height:64px;border:2px solid rgba(255,255,255,0.12);border-radius:50%;display:inline-block;position:relative;">
              <div style="width:40px;height:40px;border:2px solid {{ $carta->color_acento }};border-radius:50%;margin:10px auto;"></div>
            </div>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  {{-- ── ETIQUETA DOCUMENTO ──────────────────────────────────────────────── --}}
  <tr>
    <td style="background-color:#ffffff;border-left:1px solid #ebebeb;border-right:1px solid #ebebeb;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="padding:0 48px;">
        <tr>
          <td style="border-bottom:2px solid {{ $carta->color_acento }};padding:12px 0;">
            <p style="margin:0;color:{{ $carta->color_primario }};font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;">
              Carta de Presentación
            </p>
          </td>
          <td style="border-bottom:2px solid #f0f0f0;padding:12px 0;text-align:right;">
            <p style="margin:0;color:#b0b0b0;font-size:10px;letter-spacing:0.5px;">
              {{ now()->format('d \d\e F \d\e Y') }}
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  {{-- ── CUERPO ──────────────────────────────────────────────────────────── --}}
  <tr>
    <td style="background-color:#ffffff;padding:40px 48px 32px;border-left:1px solid #ebebeb;border-right:1px solid #ebebeb;">

      <p style="margin:0 0 8px;color:{{ $carta->color_texto }};font-size:15px;font-weight:500;">
        {{ $carta->saludo }}
      </p>

      <p style="margin:0 0 32px;color:{{ $carta->color_texto }};font-size:14px;line-height:1.9;opacity:0.85;">
        {{ $carta->intro }}
      </p>

      {{-- Servicios --}}
      @if($carta->mostrar_servicios && $servicios->count())
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;">
          <tr>
            <td style="padding-bottom:16px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="vertical-align:middle;padding-right:12px;" width="28">
                    {{-- Ícono corporativo SVG --}}
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <rect x="1" y="1" width="18" height="18" rx="4" stroke="{{ $carta->color_acento }}" stroke-width="1.5" fill="none"/>
                      <rect x="5" y="5" width="10" height="2" rx="1" fill="{{ $carta->color_acento }}"/>
                      <rect x="5" y="9" width="7" height="2" rx="1" fill="{{ $carta->color_primario }}" opacity="0.4"/>
                      <rect x="5" y="13" width="9" height="2" rx="1" fill="{{ $carta->color_primario }}" opacity="0.4"/>
                    </svg>
                  </td>
                  <td>
                    <p style="margin:0;color:{{ $carta->color_primario }};font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;">
                      {{ $carta->servicios_titulo }}
                    </p>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          @foreach($servicios as $i => $servicio)
          <tr>
            <td style="padding-bottom:10px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="background-color:#fafafa;border-radius:8px;border:1px solid #f0f0f0;overflow:hidden;">
                <tr>
                  <td width="4" style="background:linear-gradient(180deg,{{ $carta->color_acento }},{{ $carta->color_primario }});border-radius:8px 0 0 8px;">&nbsp;</td>
                  <td style="padding:14px 18px 14px 16px;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td>
                          <p style="margin:0 0 3px;color:{{ $carta->color_primario }};font-size:13px;font-weight:700;">
                            {{ $servicio->titulo }}
                          </p>
                          @if($servicio->descripcion)
                            <p style="margin:0;color:#777777;font-size:12px;line-height:1.6;">{{ $servicio->descripcion }}</p>
                          @endif
                        </td>
                        <td width="36" style="text-align:right;vertical-align:top;">
                          <span style="display:inline-block;background-color:{{ $carta->color_acento }}1a;color:{{ $carta->color_acento }};font-size:10px;font-weight:700;padding:3px 7px;border-radius:4px;letter-spacing:0.5px;">
                            {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                          </span>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          @endforeach
        </table>
      @endif

      {{-- ── Equipo ──────────────────────────────────────────────────────── --}}
      @if($carta->mostrar_equipo)
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;">
        <tr>
          <td style="padding-bottom:16px;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="vertical-align:middle;padding-right:12px;" width="28">
                  <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="7" cy="6" r="3" stroke="{{ $carta->color_acento }}" stroke-width="1.3" fill="none"/>
                    <circle cx="13" cy="6" r="3" stroke="{{ $carta->color_primario }}" stroke-width="1.3" fill="none" opacity="0.5"/>
                    <path d="M1 17C1 13.7 3.7 11 7 11C10.3 11 13 13.7 13 17" stroke="{{ $carta->color_acento }}" stroke-width="1.3" fill="none" stroke-linecap="round"/>
                    <path d="M13 11C16.3 11 19 13.7 19 17" stroke="{{ $carta->color_primario }}" stroke-width="1.3" fill="none" stroke-linecap="round" opacity="0.5"/>
                  </svg>
                </td>
                <td>
                  <p style="margin:0;color:{{ $carta->color_primario }};font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;">Nuestro equipo</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        @if($equipo->count())
          @foreach($equipo as $miembro)
          <tr>
            <td style="padding-bottom:10px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="background-color:#fafafa;border-radius:8px;border:1px solid #f0f0f0;">
                <tr>
                  <td width="4" style="background:linear-gradient(180deg,{{ $carta->color_acento }},{{ $carta->color_primario }});border-radius:8px 0 0 8px;">&nbsp;</td>
                  <td style="padding:12px 16px;">
                    <p style="margin:0 0 2px;color:{{ $carta->color_primario }};font-size:13px;font-weight:700;">{{ $miembro->nombre }}</p>
                    @if($miembro->cargo)
                      <p style="margin:0 0 2px;color:{{ $carta->color_acento }};font-size:11px;font-weight:600;">{{ $miembro->cargo }}</p>
                    @endif
                    @if($miembro->bio)
                      <p style="margin:0;color:#888888;font-size:12px;line-height:1.5;">{{ $miembro->bio }}</p>
                    @endif
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          @endforeach
        @else
          <tr>
            <td style="padding:14px 18px;background-color:#fff8f0;border:1px dashed {{ $carta->color_acento }}55;border-radius:8px;text-align:center;">
              <p style="margin:0;color:{{ $carta->color_acento }};font-size:12px;">
                &#9888; Completa la información del equipo en el panel CMS → <strong>Equipo</strong>
              </p>
            </td>
          </tr>
        @endif
      </table>
      @endif {{-- mostrar_equipo --}}

      {{-- Cierre --}}
      <p style="margin:0 0 36px;color:{{ $carta->color_texto }};font-size:14px;line-height:1.9;opacity:0.85;">
        {{ $carta->cierre }}
      </p>

      {{-- Firma --}}
      <table cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td width="3" style="background:linear-gradient(180deg,{{ $carta->color_acento }},{{ $carta->color_primario }});border-radius:2px;">&nbsp;&nbsp;</td>
          <td style="padding-left:16px;">
            <p style="margin:0 0 2px;color:{{ $carta->color_primario }};font-size:15px;font-weight:700;">{{ $carta->firma_nombre }}</p>
            @if($carta->firma_cargo)
              <p style="margin:0 0 2px;color:{{ $carta->color_acento }};font-size:12px;font-weight:600;letter-spacing:0.3px;">{{ $carta->firma_cargo }}</p>
            @endif
            <p style="margin:0;color:#aaaaaa;font-size:12px;">{{ $empresa->name }}</p>
          </td>
        </tr>
      </table>

    </td>
  </tr>

  {{-- ── CONTACTO ────────────────────────────────────────────────────────── --}}
  @if($carta->mostrar_contacto)
  <tr>
    <td style="background:linear-gradient(135deg,{{ $carta->color_primario }}f0 0%,{{ $carta->color_primario }} 100%);padding:28px 48px;border-left:1px solid #ebebeb;border-right:1px solid #ebebeb;">
      @if($contacto)
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          @if($contacto->telefono)
          <td style="padding-right:32px;border-right:1px solid rgba(255,255,255,0.12);">
            <p style="margin:0 0 4px;color:{{ $carta->color_acento }};font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;">Teléfono</p>
            <p style="margin:0;color:#ffffff;font-size:13px;">{{ $contacto->telefono }}</p>
          </td>
          @endif
          @if($contacto->email)
          <td style="padding:0 32px;{{ $contacto->whatsapp ? 'border-right:1px solid rgba(255,255,255,0.12);' : '' }}">
            <p style="margin:0 0 4px;color:{{ $carta->color_acento }};font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;">Email</p>
            <p style="margin:0;color:#ffffff;font-size:13px;">{{ $contacto->email }}</p>
          </td>
          @endif
          @if($contacto->whatsapp)
          <td style="padding-left:32px;">
            <p style="margin:0 0 4px;color:{{ $carta->color_acento }};font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;">WhatsApp</p>
            <p style="margin:0;color:#ffffff;font-size:13px;">{{ $contacto->whatsapp }}</p>
          </td>
          @endif
        </tr>
        @if($contacto->direccion)
        <tr>
          <td colspan="3" style="padding-top:16px;border-top:1px solid rgba(255,255,255,0.1);margin-top:16px;">
            <p style="margin:0;color:rgba(255,255,255,0.5);font-size:11px;">{{ $contacto->direccion }}</p>
          </td>
        </tr>
        @endif
      </table>
      @else
      <p style="margin:0;color:rgba(255,255,255,0.6);font-size:12px;text-align:center;">
        &#9888; Completa la información de contacto en el panel CMS → <strong style="color:#ffffff;">Contacto</strong>
      </p>
      @endif
    </td>
  </tr>
  @endif {{-- mostrar_contacto --}}

  {{-- ── FOOTER ──────────────────────────────────────────────────────────── --}}
  <tr>
    <td style="background-color:#111827;border-radius:0 0 12px 12px;padding:16px 48px;text-align:center;">
      <p style="margin:0;color:rgba(255,255,255,0.25);font-size:10px;letter-spacing:0.3px;">
        Generado por <strong style="color:rgba(255,255,255,0.4);">Mashaec ERP</strong> &nbsp;·&nbsp; {{ $empresa->name }}
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
