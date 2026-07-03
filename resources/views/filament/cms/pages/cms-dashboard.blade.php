<x-filament-panels::page>
<style>
/* ── CMS Dashboard — Light mode (darkMode: false) ──────────── */
.cms-dash {
    --v:        #7c3aed;                      /* violet-700 — contraste sólido en blanco */
    --v-soft:   rgba(124, 58, 237, 0.08);
    --v-border: rgba(124, 58, 237, 0.22);
    --v-text:   #6d28d9;                      /* para texto sobre blanco: ratio ≥4.8:1 */
    --surface:  #ffffff;
    --surface-2:#f8fafc;
    --surface-3:#f1f5f9;
    --border:   #e2e8f0;                      /* slate-200 */
    --border-2: #cbd5e1;                      /* slate-300 */
    --text:     #0f172a;                      /* slate-900 */
    --text-2:   #1e293b;                      /* slate-800 */
    --muted:    #64748b;                      /* slate-500 */
    --muted-2:  #94a3b8;                      /* slate-400 */
    --ok:       #059669;                      /* emerald-600 — contraste 4.6:1 en blanco */
    --ok-soft:  rgba(5, 150, 105, 0.08);
    --ok-border:rgba(5, 150, 105, 0.22);
    --ease:     cubic-bezier(0.23, 1, 0.32, 1);
    --r:        12px;
    --r-sm:     8px;
    --shadow-sm:0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
    --shadow:   0 4px 12px rgba(0,0,0,0.08), 0 1px 3px rgba(0,0,0,0.04);
    font-family: 'Sansation', system-ui, sans-serif;
    animation: dash-in 240ms var(--ease) both;
}

@keyframes dash-in {
    from { opacity: 0; transform: translateY(5px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Status row ────────────────────────────────────────────── */
.cms-status-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}
@media (min-width: 700px) {
    .cms-status-row { grid-template-columns: repeat(4, 1fr); }
}

.cms-status-pill {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r-sm);
    box-shadow: var(--shadow-sm);
    text-decoration: none;
    transition: border-color 160ms var(--ease), background 160ms var(--ease),
                box-shadow 160ms var(--ease), transform 120ms var(--ease);
}
@media (hover: hover) and (pointer: fine) {
    .cms-status-pill:hover {
        border-color: var(--border-2);
        background: var(--surface-2);
        box-shadow: var(--shadow);
    }
    .cms-status-pill.is-ok:hover { border-color: var(--ok-border); }
}
.cms-status-pill:active {
    transform: scale(0.98);
    box-shadow: var(--shadow-sm);
    transition-duration: 80ms;
}
.cms-status-pill:focus-visible {
    outline: 2px solid var(--v);
    outline-offset: 2px;
}
.cms-status-pill.is-ok {
    border-color: var(--ok-border);
    background: var(--ok-soft);
}

.cms-pill-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.is-ok .cms-pill-dot    { background: var(--ok); }
.is-empty .cms-pill-dot { background: var(--muted-2); }

.cms-pill-text { flex: 1; min-width: 0; }
.cms-pill-name {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.cms-pill-status {
    display: block;
    font-size: 0.7rem;
    color: var(--muted);
    margin-top: 1px;
}
.is-ok .cms-pill-status { color: var(--ok); }

.cms-pill-action {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--muted);
    white-space: nowrap;
    flex-shrink: 0;
}
.is-empty .cms-pill-action { color: var(--v-text); }

@media (max-width: 480px) {
    .cms-pill-action { display: none; }
}

/* ── Main grid ─────────────────────────────────────────────── */
.cms-main-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 14px;
    margin-top: 14px;
}
@media (min-width: 900px) {
    .cms-main-grid { grid-template-columns: 1fr 288px; }
}

/* ── Panel card ────────────────────────────────────────────── */
.cms-panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}
.cms-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    background: var(--surface-2);
    border-bottom: 1px solid var(--border);
}
.cms-panel-title {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--muted);
}

/* ── Section rows ──────────────────────────────────────────── */
.cms-row {
    display: flex;
    align-items: center;
    border-bottom: 1px solid var(--border);
    animation: row-in 280ms var(--ease) both;
    animation-delay: calc(var(--i, 0) * 45ms + 60ms);
}
.cms-row:last-child { border-bottom: none; }

@keyframes row-in {
    from { opacity: 0; transform: translateX(-4px); }
    to   { opacity: 1; transform: translateX(0); }
}

.cms-row-link {
    flex: 1;
    display: flex;
    align-items: center;
    padding: 10px 8px 10px 16px;
    gap: 11px;
    text-decoration: none;
    min-width: 0;
    transition: background 140ms var(--ease);
}
@media (hover: hover) and (pointer: fine) {
    .cms-row-link:hover { background: var(--surface-2); }
    .cms-row-link:hover .cms-row-icon {
        color: var(--v);
        border-color: var(--v-border);
        background: var(--v-soft);
    }
}
.cms-row-link:active    { background: var(--surface-3); transition-duration: 80ms; }
.cms-row-link:focus-visible {
    outline: 2px solid var(--v);
    outline-offset: -2px;
    border-radius: 2px;
}

.cms-row-icon {
    width: 32px; height: 32px;
    border-radius: var(--r-sm);
    background: var(--surface-3);
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: var(--muted);
    transition: color 140ms var(--ease), border-color 140ms var(--ease),
                background 140ms var(--ease);
}

.cms-row-body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}
.cms-row-label {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--text-2);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.cms-row-desc {
    font-size: 0.7rem;
    color: var(--muted);
    margin-top: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cms-row-count {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--muted);
    background: var(--surface-3);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 2px 9px;
    min-width: 34px;
    text-align: center;
    flex-shrink: 0;
}
.cms-row-count.has-content {
    color: var(--v-text);
    background: var(--v-soft);
    border-color: var(--v-border);
}

/* Botón + — sibling del link, siempre visible */
.cms-row-add {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px; height: 28px;
    margin-right: 12px;
    border-radius: var(--r-sm);
    border: 1px solid var(--border);
    color: var(--muted);
    background: var(--surface-2);
    text-decoration: none;
    flex-shrink: 0;
    transition: background 150ms var(--ease), border-color 150ms var(--ease),
                color 150ms var(--ease), transform 110ms var(--ease);
}
.cms-row-add::before {
    content: '';
    position: absolute;
    inset: -8px;
}
@media (hover: hover) and (pointer: fine) {
    .cms-row-add:hover {
        color: #fff;
        background: var(--v);
        border-color: var(--v);
    }
}
.cms-row-add:active { transform: scale(0.88); transition-duration: 90ms; }
.cms-row-add:focus-visible {
    outline: 2px solid var(--v);
    outline-offset: 3px;
}

/* ── Right column ──────────────────────────────────────────── */
.cms-right-col {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* ── Blog card ─────────────────────────────────────────────── */
.cms-blog-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}
.cms-blog-hero {
    padding: 18px 18px 14px;
    border-bottom: 1px solid var(--border);
}
.cms-blog-count-row {
    display: flex;
    align-items: baseline;
    gap: 6px;
    margin-bottom: 4px;
}
.cms-blog-number {
    font-size: 2rem;
    font-weight: 800;
    color: var(--text);
    line-height: 1;
    letter-spacing: -0.04em;
}
.cms-blog-label {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.07em;
}
.cms-blog-last {
    font-size: 0.72rem;
    color: var(--muted);
    margin-top: 4px;
}
.cms-blog-last strong { color: var(--text-2); font-weight: 500; }

.cms-blog-actions {
    display: flex;
    gap: 8px;
    padding: 12px 16px;
    background: var(--surface-2);
}

/* ── Botones ───────────────────────────────────────────────── */
.cms-btn-primary {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 7px 14px;
    min-height: 34px;
    background: var(--v);
    border-radius: var(--r-sm);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 600;
    text-decoration: none;
    transition: background 160ms var(--ease), box-shadow 160ms var(--ease),
                transform 110ms var(--ease);
}
@media (hover: hover) and (pointer: fine) {
    .cms-btn-primary:hover {
        background: #6d28d9;
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
    }
}
.cms-btn-primary:active { transform: scale(0.97); transition-duration: 90ms; }
.cms-btn-primary:focus-visible { outline: 2px solid var(--v); outline-offset: 2px; }

.cms-btn-ghost {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    padding: 7px 14px;
    min-height: 34px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r-sm);
    color: var(--muted);
    font-size: 0.75rem;
    font-weight: 500;
    text-decoration: none;
    transition: background 160ms var(--ease), color 160ms var(--ease),
                border-color 160ms var(--ease), transform 110ms var(--ease);
}
@media (hover: hover) and (pointer: fine) {
    .cms-btn-ghost:hover {
        background: var(--surface-3);
        color: var(--text-2);
        border-color: var(--border-2);
    }
}
.cms-btn-ghost:active { transform: scale(0.97); transition-duration: 90ms; }
.cms-btn-ghost:focus-visible { outline: 2px solid var(--v); outline-offset: 2px; }

/* ── Info card ─────────────────────────────────────────────── */
.cms-info-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}

.cms-info-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 16px;
    min-height: 44px;
    border-bottom: 1px solid var(--border);
    text-decoration: none;
    transition: background 140ms var(--ease);
}
.cms-info-row:last-child { border-bottom: none; }
@media (hover: hover) and (pointer: fine) {
    .cms-info-row:hover { background: var(--surface-2); }
}
.cms-info-row:active    { background: var(--surface-3); transition-duration: 80ms; }
.cms-info-row:focus-visible {
    outline: 2px solid var(--v);
    outline-offset: -2px;
    border-radius: 2px;
}

.cms-info-dot {
    width: 6px; height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}
.cms-info-dot.ok    { background: var(--ok); }
.cms-info-dot.empty { background: var(--muted-2); }

.cms-info-name {
    flex: 1;
    font-size: 0.8rem;
    color: var(--text-2);
    font-weight: 500;
}
.cms-info-badge {
    font-size: 0.63rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 20px;
    letter-spacing: 0.04em;
}
.cms-info-badge.ok    { background: var(--ok-soft); color: var(--ok); border: 1px solid var(--ok-border); }
.cms-info-badge.empty { background: var(--surface-3); color: var(--muted); border: 1px solid var(--border); }
.cms-info-badge.act   { background: var(--v-soft);  color: var(--v-text); border: 1px solid var(--v-border); }

/* ── API banner ─────────────────────────────────────────────── */
.cms-api-banner {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 13px;
    background: var(--v-soft);
    border: 1px solid var(--v-border);
    border-radius: var(--r-sm);
    margin-top: 2px;
}
.cms-api-label {
    font-size: 0.63rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--v-text);
    flex-shrink: 0;
}
.cms-api-url {
    font-size: 0.7rem;
    color: var(--muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-family: ui-monospace, 'SF Mono', monospace;
}

/* ── Reduced motion ────────────────────────────────────────── */
@media (prefers-reduced-motion: reduce) {
    .cms-dash,
    .cms-row { animation: none; opacity: 1; transform: none; }

    .cms-status-pill,
    .cms-row-link,
    .cms-row-add,
    .cms-btn-primary,
    .cms-btn-ghost,
    .cms-info-row { transition: none; }

    .cms-status-pill:active,
    .cms-row-add:active,
    .cms-btn-primary:active,
    .cms-btn-ghost:active { transform: none; }
}
</style>

<div class="cms-dash">

    {{-- Fila de estado: páginas singleton --}}
    <div class="cms-status-row">

        @php
            $tenant   = filament()->getTenant();
            $pillData = [
                [
                    'name'   => 'Hero / Portada',
                    'ok'     => $hasHero,
                    'edit'   => $hasHero
                        ? route('filament.cms.resources.cms-heroes.index',  ['tenant' => $tenant])
                        : route('filament.cms.resources.cms-heroes.create', ['tenant' => $tenant]),
                    'action' => $hasHero ? 'Editar' : 'Configurar',
                ],
                [
                    'name'   => 'Nosotros',
                    'ok'     => $hasAbout,
                    'edit'   => $hasAbout
                        ? route('filament.cms.resources.cms-abouts.index',  ['tenant' => $tenant])
                        : route('filament.cms.resources.cms-abouts.create', ['tenant' => $tenant]),
                    'action' => $hasAbout ? 'Editar' : 'Configurar',
                ],
                [
                    'name'   => 'Contacto',
                    'ok'     => $hasContact,
                    'edit'   => $hasContact
                        ? route('filament.cms.resources.cms-contacts.index',  ['tenant' => $tenant])
                        : route('filament.cms.resources.cms-contacts.create', ['tenant' => $tenant]),
                    'action' => $hasContact ? 'Editar' : 'Configurar',
                ],
                [
                    'name'   => 'Términos legales',
                    'ok'     => $hasTerminos,
                    'edit'   => $hasTerminos
                        ? route('filament.cms.resources.cms-terminos.index',  ['tenant' => $tenant])
                        : route('filament.cms.resources.cms-terminos.create', ['tenant' => $tenant]),
                    'action' => $hasTerminos ? 'Editar' : 'Configurar',
                ],
            ];
        @endphp

        @foreach ($pillData as $pill)
            <a href="{{ $pill['edit'] }}"
               class="cms-status-pill {{ $pill['ok'] ? 'is-ok' : 'is-empty' }}">
                <span class="cms-pill-dot"></span>
                <span class="cms-pill-text">
                    <span class="cms-pill-name">{{ $pill['name'] }}</span>
                    <span class="cms-pill-status">{{ $pill['ok'] ? 'Configurado' : 'Sin contenido' }}</span>
                </span>
                <span class="cms-pill-action">{{ $pill['action'] }} →</span>
            </a>
        @endforeach

    </div>

    {{-- Grid principal --}}
    <div class="cms-main-grid">

        {{-- Columna izquierda: secciones de contenido --}}
        <div>
            <div class="cms-panel">
                <div class="cms-panel-header">
                    <span class="cms-panel-title">Secciones del sitio web</span>
                </div>

                @php
                    $sections = [
                        [
                            'label'  => 'Servicios',
                            'desc'   => 'Lo que ofrece tu empresa',
                            'count'  => $servicesCount,
                            'icon'   => 'M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z',
                            'index'  => route('filament.cms.resources.cms-services.index',  ['tenant' => $tenant]),
                            'create' => route('filament.cms.resources.cms-services.create', ['tenant' => $tenant]),
                        ],
                        [
                            'label'  => 'Equipo',
                            'desc'   => 'Quiénes trabajan contigo',
                            'count'  => $teamCount,
                            'icon'   => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
                            'index'  => route('filament.cms.resources.cms-team-members.index',  ['tenant' => $tenant]),
                            'create' => route('filament.cms.resources.cms-team-members.create', ['tenant' => $tenant]),
                        ],
                        [
                            'label'  => 'Testimonios',
                            'desc'   => 'Reseñas y opiniones de clientes',
                            'count'  => $testimonialsCount,
                            'icon'   => 'M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z',
                            'index'  => route('filament.cms.resources.cms-testimonials.index',  ['tenant' => $tenant]),
                            'create' => route('filament.cms.resources.cms-testimonials.create', ['tenant' => $tenant]),
                        ],
                        [
                            'label'  => 'Logos de clientes',
                            'desc'   => 'Empresas que te eligen',
                            'count'  => $logosCount,
                            'icon'   => 'M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 0 1-1.125-1.125v-3.75ZM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-8.25ZM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-2.25Z',
                            'index'  => route('filament.cms.resources.cms-client-logos.index',  ['tenant' => $tenant]),
                            'create' => route('filament.cms.resources.cms-client-logos.create', ['tenant' => $tenant]),
                        ],
                        [
                            'label'  => 'Preguntas frecuentes',
                            'desc'   => 'FAQ visible en el sitio',
                            'count'  => $faqsCount,
                            'icon'   => 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z',
                            'index'  => route('filament.cms.resources.cms-faqs.index',  ['tenant' => $tenant]),
                            'create' => route('filament.cms.resources.cms-faqs.create', ['tenant' => $tenant]),
                        ],
                    ];
                @endphp

                @foreach ($sections as $section)
                    <div class="cms-row" style="--i: {{ $loop->index }}">

                        <a href="{{ $section['index'] }}"
                           class="cms-row-link"
                           aria-label="Ver {{ $section['label'] }}">
                            <span class="cms-row-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="1.5" stroke="currentColor" width="16" height="16"
                                     aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $section['icon'] }}" />
                                </svg>
                            </span>
                            <span class="cms-row-body">
                                <span class="cms-row-label">{{ $section['label'] }}</span>
                                <span class="cms-row-desc">{{ $section['desc'] }}</span>
                            </span>
                            <span class="cms-row-count {{ $section['count'] > 0 ? 'has-content' : '' }}">
                                {{ $section['count'] }}
                            </span>
                        </a>

                        <a href="{{ $section['create'] }}"
                           class="cms-row-add"
                           title="Agregar {{ $section['label'] }}"
                           aria-label="Agregar {{ $section['label'] }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="2" stroke="currentColor" width="13" height="13"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </a>

                    </div>
                @endforeach

            </div>
        </div>

        {{-- Columna derecha --}}
        <div class="cms-right-col">

            {{-- Blog --}}
            <div class="cms-blog-card">
                <div class="cms-blog-hero">
                    <div class="cms-blog-count-row">
                        <span class="cms-blog-number">{{ $postsCount }}</span>
                        <span class="cms-blog-label">Publicaciones</span>
                    </div>
                    @if ($lastPost)
                        <div class="cms-blog-last">
                            Última: <strong>{{ $lastPost->created_at->diffForHumans() }}</strong>
                        </div>
                    @else
                        <div class="cms-blog-last">Aún sin publicaciones en el blog.</div>
                    @endif
                </div>
                <div class="cms-blog-actions">
                    <a href="{{ route('filament.cms.resources.cms-posts.create', ['tenant' => $tenant]) }}"
                       class="cms-btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="2" stroke="currentColor" width="13" height="13" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Nuevo post
                    </a>
                    <a href="{{ route('filament.cms.resources.cms-posts.index', ['tenant' => $tenant]) }}"
                       class="cms-btn-ghost">
                        Ver todos
                    </a>
                </div>
            </div>

            {{-- Páginas de soporte --}}
            <div class="cms-info-card">
                <div class="cms-panel-header">
                    <span class="cms-panel-title">Páginas de soporte</span>
                </div>

                <a href="{{ $hasContact
                        ? route('filament.cms.resources.cms-contacts.index',  ['tenant' => $tenant])
                        : route('filament.cms.resources.cms-contacts.create', ['tenant' => $tenant]) }}"
                   class="cms-info-row">
                    <span class="cms-info-dot {{ $hasContact ? 'ok' : 'empty' }}"></span>
                    <span class="cms-info-name">Info de contacto</span>
                    <span class="cms-info-badge {{ $hasContact ? 'ok' : 'act' }}">
                        {{ $hasContact ? 'Listo' : 'Pendiente' }}
                    </span>
                </a>

                <a href="{{ $hasTerminos
                        ? route('filament.cms.resources.cms-terminos.index',  ['tenant' => $tenant])
                        : route('filament.cms.resources.cms-terminos.create', ['tenant' => $tenant]) }}"
                   class="cms-info-row">
                    <span class="cms-info-dot {{ $hasTerminos ? 'ok' : 'empty' }}"></span>
                    <span class="cms-info-name">Términos y condiciones</span>
                    <span class="cms-info-badge {{ $hasTerminos ? 'ok' : 'act' }}">
                        {{ $hasTerminos ? 'Listo' : 'Pendiente' }}
                    </span>
                </a>
            </div>

            {{-- API indicator --}}
            <div class="cms-api-banner">
                <span class="cms-api-label">API</span>
                <span class="cms-api-url">api/cms/{{ $empresa->slug }}/all</span>
            </div>

        </div>
    </div>

</div>
</x-filament-panels::page>
