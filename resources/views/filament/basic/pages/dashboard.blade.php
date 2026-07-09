<x-filament-panels::page>
    <style>
        /* ── Hub de inicio — light mode, tokens del proyecto ── */
        .hub { max-width: 1120px; }

        /* Cabecera */
        .hub-head { display:flex; align-items:center; gap:16px; margin-bottom:1.75rem; flex-wrap:wrap; }
        .hub-avatar {
            flex:0 0 auto; width:54px; height:54px; border-radius:14px;
            display:grid; place-items:center; overflow:hidden;
            background:#eef2ff; color:#4338ca; font-weight:700; font-size:1.35rem; border:1px solid #e2e8f0;
        }
        .hub-avatar img { width:100%; height:100%; object-fit:cover; }
        .hub-headtext { flex:1 1 auto; min-width:0; }
        .hub-greeting { font-size:clamp(1.4rem,1.15rem + 1.1vw,1.85rem); font-weight:700; color:#0f172a; letter-spacing:-0.02em; margin:0; line-height:1.15; text-wrap:balance; }
        .hub-sub { margin:4px 0 0; font-size:0.875rem; color:#64748b; }
        .hub-plan {
            flex:0 0 auto; align-self:center; font-size:0.78rem; font-weight:600; color:#4338ca;
            background:#eef2ff; border:1px solid #e0e7ff; padding:5px 12px; border-radius:9999px;
        }

        .hub-section-title { font-size:0.95rem; font-weight:600; color:#334155; margin:0 0 0.875rem; }
        .hub-section { margin-bottom:2.25rem; }

        /* ── Widgets de módulo ── */
        .hub-wgrid { display:grid; grid-template-columns:repeat(auto-fill, minmax(290px, 1fr)); gap:14px; }
        .hub-w {
            display:block; padding:18px; background:#fff; border:1px solid #e2e8f0; border-radius:16px;
            text-decoration:none; box-shadow:0 1px 2px rgba(15,23,42,0.04);
            transition:border-color 160ms cubic-bezier(0.16,1,0.3,1), box-shadow 160ms cubic-bezier(0.16,1,0.3,1), transform 160ms cubic-bezier(0.16,1,0.3,1);
        }
        .hub-w-head { display:flex; align-items:center; gap:11px; margin-bottom:16px; }
        .hub-w-icon { flex:0 0 auto; width:38px; height:38px; border-radius:10px; display:grid; place-items:center; color:var(--accent); background:color-mix(in srgb, var(--accent) 12%, #fff); }
        .hub-w-icon svg { width:20px; height:20px; }
        .hub-w-title { flex:1 1 auto; font-size:0.95rem; font-weight:600; color:#0f172a; }
        .hub-w-arrow { flex:0 0 auto; color:#cbd5e1; transition:transform 160ms cubic-bezier(0.16,1,0.3,1), color 160ms ease-out; }
        .hub-w-arrow svg { width:18px; height:18px; }

        .hub-w-metrics { display:flex; gap:8px; }
        .hub-w-metric { flex:1 1 0; min-width:0; }
        .hub-w-val { display:block; font-size:1.5rem; font-weight:700; color:#0f172a; line-height:1.05; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; letter-spacing:-0.02em; }
        .hub-w-label { display:block; margin-top:3px; font-size:0.76rem; color:#64748b; }

        @media (hover:hover) and (pointer:fine) {
            .hub-w:hover { border-color:var(--accent); box-shadow:0 8px 22px rgba(15,23,42,0.08); transform:translateY(-2px); }
            .hub-w:hover .hub-w-arrow { color:var(--accent); transform:translateX(3px); }
            .hub-w:active { transform:translateY(0) scale(0.995); }
        }

        /* ── Tarjetas de acceso a panel ── */
        .hub-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(264px, 1fr)); gap:14px; }
        .hub-card { display:flex; align-items:center; gap:14px; padding:18px; background:#fff; border:1px solid #e2e8f0; border-radius:14px; text-decoration:none; box-shadow:0 1px 2px rgba(15,23,42,0.04); transition:border-color 160ms cubic-bezier(0.16,1,0.3,1), box-shadow 160ms cubic-bezier(0.16,1,0.3,1), transform 160ms cubic-bezier(0.16,1,0.3,1); }
        .hub-card-icon { flex:0 0 auto; width:44px; height:44px; border-radius:11px; display:grid; place-items:center; color:var(--accent); background:color-mix(in srgb, var(--accent) 12%, #fff); }
        .hub-card-icon svg { width:22px; height:22px; }
        .hub-card-body { display:flex; flex-direction:column; min-width:0; flex:1 1 auto; }
        .hub-card-title { font-size:0.975rem; font-weight:600; color:#0f172a; line-height:1.2; }
        .hub-card-mods { margin-top:3px; font-size:0.8rem; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .hub-card-arrow { flex:0 0 auto; color:#cbd5e1; transition:transform 160ms cubic-bezier(0.16,1,0.3,1), color 160ms ease-out; }
        .hub-card-arrow svg { width:18px; height:18px; }
        @media (hover:hover) and (pointer:fine) {
            .hub-card:hover { border-color:var(--accent); box-shadow:0 6px 18px rgba(15,23,42,0.08); transform:translateY(-2px); }
            .hub-card:hover .hub-card-arrow { color:var(--accent); transform:translateX(3px); }
            .hub-card:active { transform:translateY(0) scale(0.99); }
        }

        /* ── Entrada escalonada (parte de visible) ── */
        .reveal { animation:hub-in 380ms cubic-bezier(0.16,1,0.3,1) both; }
        .reveal:nth-child(2){animation-delay:45ms}.reveal:nth-child(3){animation-delay:90ms}
        .reveal:nth-child(4){animation-delay:135ms}.reveal:nth-child(5){animation-delay:180ms}
        .reveal:nth-child(n+6){animation-delay:220ms}
        @keyframes hub-in { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }

        .hub-empty { padding:2.5rem; text-align:center; background:#fff; border:1px dashed #cbd5e1; border-radius:14px; color:#475569; font-size:0.9rem; }

        @media (prefers-reduced-motion: reduce) {
            .hub-w, .hub-card, .hub-w-arrow, .hub-card-arrow, .reveal { transition:none; animation:none; }
            .hub-w:hover, .hub-card:hover { transform:none; }
        }
    </style>

    <div class="hub">
        {{-- Cabecera --}}
        <header class="hub-head">
            <div class="hub-avatar">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $empresa->name }}">
                @else
                    {{ $inicial }}
                @endif
            </div>
            <div class="hub-headtext">
                <h1 class="hub-greeting">{{ $saludo }}</h1>
                <p class="hub-sub">{{ $empresa->name }} · {{ $fecha }}</p>
            </div>
            <span class="hub-plan">{{ $stats['plan'] }}</span>
        </header>

        {{-- Clientes: acceso transversal de la empresa (no vive en ningún panel) --}}
        <a href="{{ $clientesUrl }}"
           style="display:flex;align-items:center;gap:14px;margin:0 0 20px;padding:16px 18px;border:1px solid #e5e7eb;border-radius:14px;background:#fff;text-decoration:none;box-shadow:0 1px 2px rgba(16,24,40,.04);transition:box-shadow .15s,border-color .15s"
           onmouseover="this.style.boxShadow='0 6px 20px rgba(16,24,40,.08)';this.style.borderColor='#c7d2fe'"
           onmouseout="this.style.boxShadow='0 1px 2px rgba(16,24,40,.04)';this.style.borderColor='#e5e7eb'">
            <span style="flex:0 0 auto;width:44px;height:44px;border-radius:12px;background:#eef2ff;display:flex;align-items:center;justify-content:center">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="#4f46e5" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/></svg>
            </span>
            <span style="flex:1;min-width:0">
                <strong style="display:block;font-size:15px;color:#111827">Clientes</strong>
                <small style="color:#6b7280">Directorio y perfiles de los clientes de {{ $empresa->name }}</small>
            </span>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#9ca3af" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
        </a>

        {{-- Widgets de módulo (resumen de actividad) --}}
        @if (count($widgets))
            <section class="hub-section">
                <h2 class="hub-section-title">Resumen de tu actividad</h2>
                <div class="hub-wgrid">
                    @foreach ($widgets as $w)
                        <a href="{{ $w['url'] }}" class="hub-w reveal" style="--accent: {{ $w['color'] }}">
                            <div class="hub-w-head">
                                <span class="hub-w-icon">@svg($w['icono'])</span>
                                <span class="hub-w-title">{{ $w['titulo'] }}</span>
                                <span class="hub-w-arrow">@svg('heroicon-o-arrow-right')</span>
                            </div>
                            <div class="hub-w-metrics">
                                @foreach ($w['metrics'] as $m)
                                    <div class="hub-w-metric">
                                        @if (! empty($m['money']))
                                            <span class="hub-w-val">${{ number_format((float) $m['value'], 0) }}</span>
                                        @else
                                            <span class="hub-w-val"
                                                  x-data="{ n:0, t:{{ (int) $m['value'] }} }"
                                                  x-init="window.matchMedia('(prefers-reduced-motion: reduce)').matches ? n=t : (()=>{let s=Math.max(1,Math.ceil(t/22));let i=setInterval(()=>{n+=s;if(n>=t){n=t;clearInterval(i)}},26)})()"
                                                  x-text="n">{{ (int) $m['value'] }}</span>
                                        @endif
                                        <span class="hub-w-label">{{ $m['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Accesos a OTROS paneles (el actual no se ofrece) --}}
        @if (count($paneles))
            <section class="hub-section">
                <h2 class="hub-section-title">Ir a otro panel</h2>
                <div class="hub-grid">
                    @foreach ($paneles as $panel)
                        <a href="{{ $panel['url'] }}" class="hub-card reveal" style="--accent: {{ $panel['color'] }}">
                            <span class="hub-card-icon">@svg($panel['icono'])</span>
                            <span class="hub-card-body">
                                <span class="hub-card-title">{{ $panel['nombre'] }}</span>
                                @if (count($panel['modulos']))
                                    <span class="hub-card-mods">{{ implode(' · ', array_slice($panel['modulos'], 0, 4)) }}@if (count($panel['modulos']) > 4) · +{{ count($panel['modulos']) - 4 }}@endif</span>
                                @endif
                            </span>
                            <span class="hub-card-arrow">@svg('heroicon-o-arrow-right')</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
