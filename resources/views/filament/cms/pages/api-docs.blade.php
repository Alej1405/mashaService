<x-filament-panels::page>
    <style>
        .ad { max-width: 940px; }
        .ad-lead { font-size:0.95rem; color:#475569; margin:0 0 1.5rem; max-width:65ch; line-height:1.6; }

        /* Base URL */
        .ad-baseurl { display:flex; align-items:center; gap:10px; flex-wrap:wrap; background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 14px; margin-bottom:1.75rem; }
        .ad-tag { font-size:0.68rem; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#64748b; }
        .ad-baseurl code { flex:1 1 auto; min-width:200px; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:0.86rem; color:#0f172a; word-break:break-all; }

        /* Tarjetas de sección */
        .ad-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:20px 22px; margin-bottom:1.25rem; }
        .ad-card h2 { font-size:1.05rem; font-weight:700; color:#0f172a; margin:0 0 4px; letter-spacing:-0.01em; }
        .ad-card p { font-size:0.9rem; color:#475569; margin:0 0 12px; line-height:1.6; }
        .ad-card p:last-child { margin-bottom:0; }
        .ad-inline { font-family:ui-monospace,monospace; font-size:0.82rem; background:#f1f5f9; color:#0f172a; padding:2px 7px; border-radius:6px; }

        /* Bloque de código (superficie oscura solo para código, convención universal) */
        .ad-code { position:relative; background:#0f172a; color:#e2e8f0; border-radius:10px; padding:14px 16px; margin:0; overflow:auto; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:0.8rem; line-height:1.55; }
        .ad-code-wrap { position:relative; }

        /* Botón copiar */
        .ad-copy { display:inline-flex; align-items:center; gap:5px; font-size:0.74rem; font-weight:600; color:#475569; background:#fff; border:1px solid #e2e8f0; border-radius:7px; padding:5px 10px; cursor:pointer; transition:all 140ms cubic-bezier(0.16,1,0.3,1); }
        .ad-copy:hover { border-color:#6366f1; color:#4f46e5; }
        .ad-copy.copied { border-color:#16a34a; color:#16a34a; }
        .ad-copy-float { position:absolute; top:9px; right:9px; background:#1e293b; color:#cbd5e1; border:1px solid #334155; }
        .ad-copy-float:hover { border-color:#6366f1; color:#fff; }

        /* Token */
        .ad-token { display:flex; align-items:center; gap:10px; flex-wrap:wrap; background:#fffbeb; border:1px solid #fde68a; border-radius:10px; padding:12px 14px; }
        .ad-token code { flex:1 1 auto; min-width:200px; font-family:ui-monospace,monospace; font-size:0.82rem; color:#92400e; word-break:break-all; }
        .ad-status { display:inline-flex; align-items:center; gap:7px; font-size:0.85rem; color:#475569; }
        .ad-dot { width:8px; height:8px; border-radius:50%; }
        .ad-dot-on { background:#16a34a; } .ad-dot-off { background:#cbd5e1; }

        /* Endpoints */
        .ad-eptitle { font-size:0.95rem; font-weight:600; color:#334155; margin:1.75rem 0 0.875rem; }
        .ad-ep { background:#fff; border:1px solid #e2e8f0; border-radius:12px; margin-bottom:10px; overflow:hidden; transition:border-color 150ms ease-out, box-shadow 150ms ease-out; }
        .ad-ep:hover { border-color:#cbd5e1; box-shadow:0 2px 8px rgba(15,23,42,0.05); }
        .ad-ep-head { display:flex; align-items:center; gap:12px; width:100%; padding:13px 16px; background:none; border:0; cursor:pointer; text-align:left; }
        .ad-method { flex:0 0 auto; font-size:0.7rem; font-weight:700; letter-spacing:0.03em; padding:3px 9px; border-radius:6px; }
        .ad-get { color:#15803d; background:#dcfce7; }
        .ad-path { flex:0 0 auto; font-family:ui-monospace,monospace; font-size:0.84rem; font-weight:600; color:#0f172a; }
        .ad-ep-desc { flex:1 1 auto; min-width:0; font-size:0.82rem; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .ad-chev { flex:0 0 auto; color:#94a3b8; transition:transform 200ms cubic-bezier(0.16,1,0.3,1); }
        .ad-chev svg { width:16px; height:16px; display:block; }
        .ad-ep-body { padding:0 16px 16px; }
        .ad-ep-body .ad-label { font-size:0.72rem; font-weight:700; letter-spacing:0.05em; text-transform:uppercase; color:#64748b; margin:0 0 8px; display:block; }

        @media (max-width:640px){ .ad-ep-desc{ display:none; } }
        @media (prefers-reduced-motion: reduce){ .ad-copy, .ad-ep, .ad-chev { transition:none; } }
    </style>

    <div class="ad"
         x-data="{
            copied: '',
            copy(text, id) {
                navigator.clipboard.writeText(text).then(() => {
                    this.copied = id;
                    setTimeout(() => { if (this.copied === id) this.copied = '' }, 1500);
                });
            }
         }">

        <p class="ad-lead">
            API REST pública para consumir el contenido de tu sitio (CMS) desde cualquier frontend.
            Todas las respuestas son JSON. Solo lectura (GET).
        </p>

        {{-- Base URL --}}
        <div class="ad-baseurl">
            <span class="ad-tag">Base URL</span>
            <code>{{ $baseUrl }}</code>
            <button class="ad-copy" :class="copied === 'base' && 'copied'" @click="copy(@js($baseUrl), 'base')">
                <span x-text="copied === 'base' ? '¡Copiado!' : 'Copiar'"></span>
            </button>
        </div>

        {{-- Autenticación --}}
        <div class="ad-card">
            <h2>Autenticación</h2>
            <p>Incluye tu token en el encabezado de cada petición:
                <span class="ad-inline">Authorization: Bearer {TU_TOKEN}</span>
            </p>

            @if ($newToken)
                <div class="ad-token">
                    <code>{{ $newToken }}</code>
                    <button class="ad-copy" :class="copied === 'tok' && 'copied'" @click="copy(@js($newToken), 'tok')">
                        <span x-text="copied === 'tok' ? '¡Copiado!' : 'Copiar token'"></span>
                    </button>
                </div>
                <p style="margin-top:10px; color:#b45309; font-size:0.82rem;">Guárdalo ahora — no se volverá a mostrar.</p>
            @else
                <p class="ad-status">
                    <span class="ad-dot {{ $tieneToken ? 'ad-dot-on' : 'ad-dot-off' }}"></span>
                    @if ($tieneToken)
                        Token activo · creado {{ $tokenCreadoEn }} · último uso {{ $tokenUsadoEn }}
                    @else
                        Sin token activo. Genera uno con el botón <strong>“Generar nuevo token”</strong> de arriba.
                    @endif
                </p>
            @endif
        </div>

        {{-- Ejemplo de uso --}}
        <div class="ad-card">
            <h2>Ejemplo de uso</h2>
            <p>Obtener el hero del sitio con <span class="ad-inline">fetch</span>:</p>
            <div class="ad-code-wrap">
@php
$snippet = "const res = await fetch(\"{$baseUrl}/hero\", {\n  headers: { Authorization: \"Bearer \" + TOKEN }\n});\nconst data = await res.json();";
@endphp
                <pre class="ad-code">{{ $snippet }}</pre>
                <button class="ad-copy ad-copy-float" :class="copied === 'snip' && 'copied'" @click="copy(@js($snippet), 'snip')">
                    <span x-text="copied === 'snip' ? '¡Copiado!' : 'Copiar'"></span>
                </button>
            </div>
        </div>

        {{-- Endpoints --}}
        <h3 class="ad-eptitle">Endpoints ({{ count($endpoints) }})</h3>

        @foreach ($endpoints as $i => $ep)
            <div class="ad-ep" x-data="{ open: false }">
                <button class="ad-ep-head" @click="open = !open">
                    <span class="ad-method ad-get">{{ $ep['metodo'] }}</span>
                    <span class="ad-path">{{ $ep['ruta'] }}</span>
                    <span class="ad-ep-desc">{{ $ep['desc'] }}</span>
                    <span class="ad-chev" :style="open && 'transform:rotate(90deg)'">@svg('heroicon-o-chevron-right')</span>
                </button>
                <div x-show="open" x-collapse.duration.250ms style="display:none;">
                    <div class="ad-ep-body">
                        <span class="ad-label">Petición</span>
                        <div class="ad-code-wrap" style="margin-bottom:14px;">
@php $full = $baseUrl . $ep['ruta']; @endphp
                            <pre class="ad-code">GET {{ $full }}</pre>
                            <button class="ad-copy ad-copy-float" :class="copied === 'u{{ $i }}' && 'copied'" @click="copy(@js($full), 'u{{ $i }}')">
                                <span x-text="copied === 'u{{ $i }}' ? '✓' : 'Copiar'"></span>
                            </button>
                        </div>
                        <span class="ad-label">Respuesta</span>
                        <div class="ad-code-wrap">
                            <pre class="ad-code">{{ $ep['ejemplo'] }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
