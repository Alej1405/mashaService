<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>{{ $carta->asunto }}</title>
</head>
<body style="margin:0;padding:0;background-color:#0f1117;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#0f1117;padding:40px 16px;">
<tr><td align="center">
<table width="640" cellpadding="0" cellspacing="0" border="0" style="max-width:640px;width:100%;">

  {{-- ── HEADER ─────────────────────────────────────────────────────────── --}}
  <tr>
    <td style="background:linear-gradient(160deg,#0f1117 0%,{{ $carta->color_primario }}55 50%,#0f1117 100%);border-radius:12px 12px 0 0;border:1px solid rgba(255,255,255,0.06);border-bottom:none;padding:48px 48px 40px;overflow:hidden;">

      {{-- Línea decorativa superior --}}
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:36px;">
        <tr>
          <td style="height:1px;background:linear-gradient(90deg,transparent,{{ $carta->color_acento }},transparent);"></td>
        </tr>
      </table>

      {{-- Logo + nombre + elemento decorativo --}}
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="vertical-align:middle;">
            @if($empresa->logo_path)
              <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path) }}"
                   alt="{{ $empresa->name }}"
                   style="max-height:52px;max-width:160px;object-fit:contain;display:block;margin-bottom:20px;filter:brightness(0) invert(1);opacity:0.85;">
            @endif
            <h1 style="margin:0 0 8px;color:#ffffff;font-size:26px;font-weight:300;letter-spacing:2px;text-transform:uppercase;line-height:1.2;">
              {{ $empresa->name }}
            </h1>
            <table cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td width="24" style="height:1px;background-color:{{ $carta->color_acento }};vertical-align:middle;border-radius:1px;"></td>
                <td style="padding-left:10px;">
                  <p style="margin:0;color:{{ $carta->color_acento }};font-size:10px;font-weight:700;letter-spacing:3px;text-transform:uppercase;">
                    Carta de Presentación
                  </p>
                </td>
              </tr>
            </table>
          </td>
          <td width="80" style="text-align:right;vertical-align:middle;">
            {{-- Elemento geométrico decorativo élite --}}
            <svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="1" y="1" width="62" height="62" rx="6" stroke="{{ $carta->color_acento }}" stroke-width="0.5" stroke-dasharray="4 4"/>
              <rect x="10" y="10" width="44" height="44" rx="4" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/>
              <circle cx="32" cy="32" r="12" stroke="{{ $carta->color_acento }}" stroke-width="0.8" fill="none"/>
              <circle cx="32" cy="32" r="3" fill="{{ $carta->color_acento }}" fill-opacity="0.5"/>
              <line x1="32" y1="16" x2="32" y2="20" stroke="{{ $carta->color_acento }}" stroke-width="1"/>
              <line x1="32" y1="44" x2="32" y2="48" stroke="{{ $carta->color_acento }}" stroke-width="1"/>
              <line x1="16" y1="32" x2="20" y2="32" stroke="{{ $carta->color_acento }}" stroke-width="1"/>
              <line x1="44" y1="32" x2="48" y2="32" stroke="{{ $carta->color_acento }}" stroke-width="1"/>
            </svg>
          </td>
        </tr>
      </table>

      {{-- Línea decorativa inferior --}}
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:36px;">
        <tr>
          <td style="height:1px;background:linear-gradient(90deg,{{ $carta->color_acento }},transparent);"></td>
        </tr>
      </table>

    </td>
  </tr>

  {{-- ── CUERPO ──────────────────────────────────────────────────────────── --}}
  <tr>
    <td style="background-color:#16191f;padding:40px 48px 36px;border-left:1px solid rgba(255,255,255,0.06);border-right:1px solid rgba(255,255,255,0.06);">

      {{-- Fecha --}}
      <p style="margin:0 0 28px;color:rgba(255,255,255,0.25);font-size:10px;letter-spacing:2px;text-transform:uppercase;">
        {{ now()->format('d \d\e F \d\e Y') }}
      </p>

      <p style="margin:0 0 8px;color:rgba(255,255,255,0.9);font-size:15px;font-weight:500;">
        {{ $carta->saludo }}
      </p>

      <p style="margin:0 0 36px;color:rgba(255,255,255,0.65);font-size:14px;line-height:1.9;">
        {{ $carta->intro }}
      </p>

      {{-- Servicios --}}
      @if($servicios->count())
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:36px;">

          {{-- Título servicios --}}
          <tr>
            <td style="padding-bottom:20px;">
              <table cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="padding-right:10px;vertical-align:middle;">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M9 1L11.2 6.5H17L12.4 10L14.1 16L9 12.5L3.9 16L5.6 10L1 6.5H6.8L9 1Z" stroke="{{ $carta->color_acento }}" stroke-width="1" fill="{{ $carta->color_acento }}" fill-opacity="0.15"/>
                    </svg>
                  </td>
                  <td>
                    <p style="margin:0;color:{{ $carta->color_acento }};font-size:10px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;">
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
                     style="background:linear-gradient(135deg,rgba(255,255,255,0.03),rgba(255,255,255,0.06));border:1px solid rgba(255,255,255,0.08);border-radius:8px;overflow:hidden;">
                <tr>
                  {{-- Franja acento izquierda --}}
                  <td width="3" style="background:linear-gradient(180deg,{{ $carta->color_acento }},{{ $carta->color_primario }});border-radius:8px 0 0 8px;">&nbsp;&nbsp;</td>
                  <td style="padding:14px 18px;">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td>
                          <p style="margin:0 0 4px;color:#ffffff;font-size:13px;font-weight:600;letter-spacing:0.2px;">
                            {{ $servicio->titulo }}
                          </p>
                          @if($servicio->descripcion)
                            <p style="margin:0;color:rgba(255,255,255,0.45);font-size:12px;line-height:1.6;">{{ $servicio->descripcion }}</p>
                          @endif
                        </td>
                        <td width="40" style="text-align:right;vertical-align:top;">
                          <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="14" cy="14" r="13" stroke="{{ $carta->color_acento }}" stroke-width="0.5" fill="{{ $carta->color_acento }}" fill-opacity="0.05"/>
                            <text x="14" y="19" text-anchor="middle" fill="{{ $carta->color_acento }}" font-size="10" font-weight="700" font-family="Helvetica,Arial,sans-serif">{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}</text>
                          </svg>
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

      {{-- Separador --}}
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;">
        <tr>
          <td style="height:1px;background:linear-gradient(90deg,{{ $carta->color_acento }}60,transparent);"></td>
        </tr>
      </table>

      {{-- ── Equipo ──────────────────────────────────────────────────────── --}}
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:36px;">
        <tr>
          <td style="padding-bottom:20px;">
            <table cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td style="padding-right:10px;vertical-align:middle;">
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="7" cy="6" r="3" stroke="{{ $carta->color_acento }}" stroke-width="1" fill="none"/>
                    <circle cx="12" cy="6" r="2.5" stroke="rgba(255,255,255,0.2)" stroke-width="1" fill="none"/>
                    <path d="M1 16C1 12.7 3.7 10 7 10C10.3 10 13 12.7 13 16" stroke="{{ $carta->color_acento }}" stroke-width="1" fill="none" stroke-linecap="round"/>
                    <path d="M13 10C15.2 10 17 11.8 17 14" stroke="rgba(255,255,255,0.2)" stroke-width="1" fill="none" stroke-linecap="round"/>
                  </svg>
                </td>
                <td>
                  <p style="margin:0;color:{{ $carta->color_acento }};font-size:10px;font-weight:700;letter-spacing:2.5px;text-transform:uppercase;">Nuestro equipo</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        @if($equipo->count())
          @foreach($equipo as $i => $miembro)
          <tr>
            <td style="padding-bottom:10px;">
              <table width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="background:linear-gradient(135deg,rgba(255,255,255,0.03),rgba(255,255,255,0.06));border:1px solid rgba(255,255,255,0.08);border-radius:8px;overflow:hidden;">
                <tr>
                  <td width="3" style="background:linear-gradient(180deg,{{ $carta->color_acento }},{{ $carta->color_primario }});border-radius:8px 0 0 8px;">&nbsp;&nbsp;</td>
                  <td style="padding:14px 18px;">
                    <p style="margin:0 0 3px;color:#ffffff;font-size:13px;font-weight:600;">{{ $miembro->nombre }}</p>
                    @if($miembro->cargo)
                      <p style="margin:0 0 3px;color:{{ $carta->color_acento }};font-size:11px;font-weight:600;letter-spacing:0.5px;text-transform:uppercase;">{{ $miembro->cargo }}</p>
                    @endif
                    @if($miembro->bio)
                      <p style="margin:0;color:rgba(255,255,255,0.4);font-size:12px;line-height:1.6;">{{ $miembro->bio }}</p>
                    @endif
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          @endforeach
        @else
          <tr>
            <td style="padding:14px 18px;background:rgba(255,255,255,0.03);border:1px dashed {{ $carta->color_acento }}44;border-radius:8px;text-align:center;">
              <p style="margin:0;color:{{ $carta->color_acento }};font-size:12px;">
                &#9888; Completa la información del equipo en el panel CMS → <strong>Equipo</strong>
              </p>
            </td>
          </tr>
        @endif
      </table>

      {{-- Separador --}}
      <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;">
        <tr>
          <td style="height:1px;background:linear-gradient(90deg,{{ $carta->color_acento }}40,transparent);"></td>
        </tr>
      </table>

      {{-- Cierre --}}
      <p style="margin:0 0 36px;color:rgba(255,255,255,0.65);font-size:14px;line-height:1.9;">
        {{ $carta->cierre }}
      </p>

      {{-- Firma --}}
      <table cellpadding="0" cellspacing="0" border="0"
             style="background:linear-gradient(135deg,rgba(255,255,255,0.03),rgba(255,255,255,0.06));border:1px solid rgba(255,255,255,0.08);border-radius:8px;padding:18px 24px;">
        <tr>
          <td style="padding-right:16px;vertical-align:middle;">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="20" cy="20" r="19.5" stroke="{{ $carta->color_acento }}" stroke-width="0.5" fill="{{ $carta->color_acento }}" fill-opacity="0.05"/>
              <circle cx="20" cy="15" r="5" stroke="{{ $carta->color_acento }}" stroke-width="1" fill="none"/>
              <path d="M10 32C10 26.5 14.5 23 20 23C25.5 23 30 26.5 30 32" stroke="{{ $carta->color_acento }}" stroke-width="1" fill="none" stroke-linecap="round"/>
            </svg>
          </td>
          <td>
            <p style="margin:0 0 2px;color:#ffffff;font-size:15px;font-weight:600;">{{ $carta->firma_nombre }}</p>
            @if($carta->firma_cargo)
              <p style="margin:0 0 2px;color:{{ $carta->color_acento }};font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;">{{ $carta->firma_cargo }}</p>
            @endif
            <p style="margin:0;color:rgba(255,255,255,0.3);font-size:11px;">{{ $empresa->name }}</p>
          </td>
        </tr>
      </table>

    </td>
  </tr>

  {{-- ── CONTACTO ────────────────────────────────────────────────────────── --}}
  <tr>
    <td style="background-color:#0f1117;padding:28px 48px;border:1px solid rgba(255,255,255,0.06);border-top:none;border-bottom:none;">
      @if(! $contacto)
      <p style="margin:0;color:{{ $carta->color_acento }};font-size:12px;text-align:center;">
        &#9888; Completa la información de contacto en el panel CMS → <strong style="color:rgba(255,255,255,0.6);">Contacto</strong>
      </p>
      @else
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td colspan="3" style="padding-bottom:16px;">
            <table cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td width="20" style="height:1px;background-color:{{ $carta->color_acento }};vertical-align:middle;border-radius:1px;"></td>
                <td style="padding-left:8px;">
                  <p style="margin:0;color:rgba(255,255,255,0.3);font-size:9px;letter-spacing:2px;text-transform:uppercase;">Información de contacto</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          @if($contacto->telefono)
          <td style="padding-right:24px;vertical-align:top;">
            <p style="margin:0 0 3px;color:{{ $carta->color_acento }};font-size:9px;letter-spacing:1.5px;text-transform:uppercase;">Teléfono</p>
            <p style="margin:0;color:rgba(255,255,255,0.7);font-size:13px;">{{ $contacto->telefono }}</p>
          </td>
          @endif
          @if($contacto->email)
          <td style="padding-right:24px;vertical-align:top;">
            <p style="margin:0 0 3px;color:{{ $carta->color_acento }};font-size:9px;letter-spacing:1.5px;text-transform:uppercase;">Email</p>
            <p style="margin:0;color:rgba(255,255,255,0.7);font-size:13px;">{{ $contacto->email }}</p>
          </td>
          @endif
          @if($contacto->whatsapp)
          <td style="vertical-align:top;">
            <p style="margin:0 0 3px;color:{{ $carta->color_acento }};font-size:9px;letter-spacing:1.5px;text-transform:uppercase;">WhatsApp</p>
            <p style="margin:0;color:rgba(255,255,255,0.7);font-size:13px;">{{ $contacto->whatsapp }}</p>
          </td>
          @endif
        </tr>
        @if($contacto->direccion)
        <tr>
          <td colspan="3" style="padding-top:14px;">
            <p style="margin:0;color:rgba(255,255,255,0.25);font-size:11px;">{{ $contacto->direccion }}</p>
          </td>
        </tr>
        @endif
      </table>
      @endif
    </td>
  </tr>

  {{-- ── FOOTER ──────────────────────────────────────────────────────────── --}}
  <tr>
    <td style="background-color:#0f1117;border-radius:0 0 12px 12px;border:1px solid rgba(255,255,255,0.06);border-top:none;padding:0 48px 24px;">
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
          <td style="height:1px;background:linear-gradient(90deg,transparent,{{ $carta->color_acento }},transparent);margin-bottom:16px;display:block;"></td>
        </tr>
        <tr>
          <td style="padding-top:16px;text-align:center;">
            <p style="margin:0;color:rgba(255,255,255,0.15);font-size:10px;letter-spacing:0.5px;">
              Generado por <strong style="color:rgba(255,255,255,0.3);">Mashaec ERP</strong> &nbsp;·&nbsp; {{ $empresa->name }}
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
