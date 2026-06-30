<x-filament-panels::page>
{{--
|───────────────────────────────────────────────────────────────────────────────
|  Admin Dashboard — Mashaec ERP · Super Administrador
|───────────────────────────────────────────────────────────────────────────────
|
|  PROPÓSITO
|  Vista de control central para el super-admin. Responde de un vistazo:
|  ¿El sistema está operacional? ¿Cuántas empresas activas? ¿Hay incidentes?
|
|  TEMA: Light mode. Fondo blanco. darkMode(false) en AdminPanelProvider.
|
|  DATOS (AdminDashboard.php)
|  ┌─ getKpis()           → total, activas, inactivas, online, incidentes, nuevas, porPlan
|  ├─ getEmpresasMatriz() → empresas con estado de módulos (N+1 evitado)
|  ├─ getActividad()      → últimos 8 SystemEvent para el feed
|  └─ config('erp_features') → catálogo canónico: 9 módulos con label, color, icon
|
|  JERARQUÍA VISUAL
|  1. Barra de estado     → respuesta inmediata "¿todo bien?"
|  2. Tres KPI tiles      → datos que MANDAN (número grande peso 700)
|  3. Matriz central      → herramienta operativa (empresa × módulo)
|  4. Sidebar             → planes, actividad reciente, acciones rápidas
|
|  TOKENS (light mode, WCAG AA verificado)
|  Fondo:      #f8fafc (slate-50)
|  Card:       #ffffff · borde #e2e8f0 · sombra 0 1px 3px rgba(0,0,0,0.06)
|  Texto:      #0f172a primario (~17:1) · #475569 secundario (~5.9:1)
|  Muted:      #64748b (slate-500, mínimo WCAG AA ~4.6:1)
|  Acento:     #6366f1 (indigo) — solo acciones y estados activos
|  Verde ok:   #16a34a · Rojo error: #dc2626 · Amarillo warn: #d97706
|  Font:       Sansation
|
|  RUTAS
|  filament.admin.resources.empresas.*
|  filament.admin.resources.service-invoices.*
|  filament.admin.resources.system-events.*
|  filament.admin.pages.sesiones-activas-page
|
|  PIPELINE UI APLICADO
|  dashboard-design → impeccable (contraste WCAG) → emil (micro-detalles)
|───────────────────────────────────────────────────────────────────────────────
--}}

@php
    $kpis      = $this->getKpis();
    $empresas  = $this->getEmpresasMatriz();
    $actividad = $this->getActividad();
    $modulos   = config('erp_features', []);

    /**
     * Colores por módulo — tomados de erp_features.php 'color'.
     * dot: color sólido para puntos activos
     * bg:  tinte suave para fondo del punto (light mode, contraste con blanco)
     * border: borde del punto activo
     * label: abreviatura para cabecera de la matriz
     */
    $moduloColors = [
        'finanzas'   => ['dot' => '#7c3aed', 'bg' => '#f5f3ff', 'border' => '#ddd6fe', 'label' => 'FIN'],
        'tesoreria'  => ['dot' => '#059669', 'bg' => '#f0fdf4', 'border' => '#bbf7d0', 'label' => 'TES'],
        'compras'    => ['dot' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fde68a', 'label' => 'COM'],
        'inventario' => ['dot' => '#2563eb', 'bg' => '#eff6ff', 'border' => '#bfdbfe', 'label' => 'INV'],
        'ventas'     => ['dot' => '#16a34a', 'bg' => '#f0fdf4', 'border' => '#bbf7d0', 'label' => 'VEN'],
        'produccion' => ['dot' => '#ea580c', 'bg' => '#fff7ed', 'border' => '#fed7aa', 'label' => 'PRO'],
        'marketing'  => ['dot' => '#db2777', 'bg' => '#fdf2f8', 'border' => '#f9a8d4', 'label' => 'MKT'],
        'tienda'     => ['dot' => '#0891b2', 'bg' => '#ecfeff', 'border' => '#a5f3fc', 'label' => 'TDA'],
        'logistica'  => ['dot' => '#475569', 'bg' => '#f8fafc', 'border' => '#cbd5e1', 'label' => 'LOG'],
    ];

    $sistemaOk = $kpis['incidentes'] === 0;
@endphp

<style>
/*─────────────────────────────────────────────────────────────────────────────
  ADMIN DASHBOARD — scope .adash — light mode
  Tokens: fondo blanco, acento #6366f1, font Sansation
  Contraste verificado WCAG AA: texto mínimo #64748b sobre #fff (~4.6:1)
─────────────────────────────────────────────────────────────────────────────*/
.adash {
    font-family: 'Sansation', system-ui, sans-serif;
    color: #0f172a;
}

/* ── Animaciones ──────────────────────────────────────────────── */
@keyframes adash-pulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: .35; }
}
@keyframes adash-fadein {
    from { opacity: 0; transform: translateY(5px); }
    to   { opacity: 1; transform: translateY(0); }
}
@media (prefers-reduced-motion: reduce) {
    @keyframes adash-pulse  { from {} to {} }
    @keyframes adash-fadein { from {} to {} }
    .adash * { transition: none !important; animation: none !important; }
}

/* ── Barra de estado del sistema ──────────────────────────────── */
.adash-statusbar {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 0.625rem 1.25rem;
    font-size: 0.78rem;
    font-weight: 500;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.adash-statusbar .sep { color: #cbd5e1; }
.adash-stat { display: flex; align-items: center; gap: 0.4rem; }
.adash-stat .lbl { color: #64748b; font-weight: 400; }
.adash-stat .val { color: #0f172a; font-weight: 700; }
.adash-stat .val.ok    { color: #16a34a; }
.adash-stat .val.warn  { color: #d97706; }
.adash-stat .val.error { color: #dc2626; }

/* Indicador de pulso (●) */
.adash-pulse {
    width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
}
.adash-pulse.ok {
    background: #22c55e;
    animation: adash-pulse 2.4s ease-in-out infinite;
}
.adash-pulse.error {
    background: #ef4444;
    animation: adash-pulse 1.2s ease-in-out infinite;
}
.adash-pulse.idle { background: #cbd5e1; }

/* ── KPI Tiles ────────────────────────────────────────────────── */
.adash-kpis {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.adash-kpi {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.875rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    animation: adash-fadein 280ms ease-out both;
    position: relative;
    overflow: hidden;
    transition: box-shadow 180ms ease-out, border-color 180ms ease-out;
}
.adash-kpi:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.05);
    border-color: #c7d2fe;
}
.adash-kpi:nth-child(1) { animation-delay: 0ms;   }
.adash-kpi:nth-child(2) { animation-delay: 50ms;  }
.adash-kpi:nth-child(3) { animation-delay: 100ms; }

/* Franja de color en el tope del KPI (indicador sin sobrecargar) */
.adash-kpi-stripe {
    position: absolute;
    top: 0; left: 1.5rem; right: 1.5rem;
    height: 2px;
    border-radius: 0 0 2px 2px;
}

.adash-kpi-label {
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
    letter-spacing: 0.04em;
    margin-top: 0.375rem;
}
.adash-kpi-value {
    font-size: 2.75rem;
    font-weight: 700;
    line-height: 1;
    color: #0f172a;
    letter-spacing: -0.02em;
    margin-top: 0.25rem;
}
.adash-kpi-value.ok    { color: #16a34a; }
.adash-kpi-value.error { color: #dc2626; }
.adash-kpi-sub {
    font-size: 0.73rem;
    color: #64748b;
    margin-top: 0.375rem;
    line-height: 1.4;
}

/* ── Layout principal (matriz + sidebar) ─────────────────────── */
.adash-main {
    display: grid;
    grid-template-columns: 1fr 268px;
    gap: 1.25rem;
    align-items: start;
}

/* ── Card genérica ────────────────────────────────────────────── */
.adash-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.875rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
    animation: adash-fadein 280ms ease-out both;
}
.adash-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    background: #f8fafc;
}
.adash-card-title {
    font-size: 0.72rem;
    font-weight: 700;
    color: #475569;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.adash-card-body { padding: 0; }

/* Leyenda de la matriz */
.adash-legend {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    font-size: 0.7rem;
    color: #64748b;
}
.adash-legend span { display: flex; align-items: center; gap: 4px; }

/* ── Tabla matriz empresa × módulo ───────────────────────────── */
.adash-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
}
.adash-table thead th {
    padding: 0.5rem 0.375rem;
    text-align: center;
    font-size: 0.62rem;
    font-weight: 700;
    color: #64748b;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #f1f5f9;
    background: #f8fafc;
    white-space: nowrap;
}
.adash-table thead th.col-company {
    text-align: left;
    padding-left: 1.25rem;
    min-width: 200px;
}
.adash-table thead th.col-actions { min-width: 48px; }

/* Cabecera de módulo: punto de color + abreviatura */
.adash-mod-head {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
}
.adash-mod-head-dot {
    width: 6px; height: 6px;
    border-radius: 50%; flex-shrink: 0;
}
.adash-mod-head-label { font-size: 0.58rem; color: #64748b; }

/* Tooltip CSS-only */
.adash-tt {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.adash-tt .tip {
    display: none;
    position: absolute;
    bottom: calc(100% + 6px);
    left: 50%;
    transform: translateX(-50%);
    background: #0f172a;
    color: #f8fafc;
    font-size: 0.68rem;
    white-space: nowrap;
    padding: 4px 10px;
    border-radius: 6px;
    pointer-events: none;
    z-index: 40;
    font-weight: 500;
}
.adash-tt .tip::after {
    content: '';
    position: absolute;
    top: 100%; left: 50%;
    transform: translateX(-50%);
    border: 4px solid transparent;
    border-top-color: #0f172a;
}
.adash-tt:hover .tip { display: block; }

/* Filas de la tabla */
.adash-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 130ms ease-out;
}
.adash-table tbody tr:last-child { border-bottom: none; }
.adash-table tbody tr:hover { background: #f8fafc; }
.adash-table tbody td {
    padding: 0.625rem 0.375rem;
    vertical-align: middle;
    text-align: center;
}
.adash-table tbody td.col-company {
    text-align: left;
    padding-left: 1.25rem;
    padding-right: 0.75rem;
}
.adash-table tbody td.col-actions { padding-right: 1rem; }

/* Celda de empresa */
.adash-company-name {
    font-size: 0.83rem;
    font-weight: 600;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.adash-company-meta {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    margin-top: 3px;
}
.adash-online-dot {
    width: 7px; height: 7px;
    border-radius: 50%; flex-shrink: 0;
}
.adash-online-dot.yes {
    background: #22c55e;
    animation: adash-pulse 2.4s ease-in-out infinite;
}
.adash-online-dot.no { background: #e2e8f0; }

/* Badges de plan */
.adash-plan {
    font-size: 0.6rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.adash-plan.enterprise { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
.adash-plan.pro        { background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; }
.adash-plan.basic      { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
.adash-inactive-tag    { font-size: 0.58rem; color: #dc2626; font-weight: 700; letter-spacing: 0.03em; }
.adash-users-count     { font-size: 0.68rem; color: #64748b; }

/* Punto de estado de módulo en la matriz */
.adash-mod-dot {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px; height: 24px;
    border-radius: 6px;
    transition: transform 120ms ease-out;
    cursor: default;
}
.adash-mod-dot:hover { transform: scale(1.2); }
.adash-mod-dot.complete {
    border-width: 1px;
    border-style: solid;
}
.adash-mod-dot.partial {
    border-width: 1px;
    border-style: dashed;
    opacity: .65;
}
.adash-mod-dot.inactive {
    background: #f1f5f9 !important;
    border: 1px solid #e2e8f0 !important;
}
.adash-mod-dot-inner {
    width: 8px; height: 8px;
    border-radius: 50%;
}
.adash-mod-dot.inactive .adash-mod-dot-inner { background: #cbd5e1 !important; }

/* Botón de acción en la tabla */
.adash-row-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px; height: 30px;
    border-radius: 7px;
    color: #64748b;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    transition: background 150ms ease-out, color 150ms ease-out, border-color 150ms ease-out;
}
.adash-row-action:hover {
    background: #eef2ff;
    color: #4f46e5;
    border-color: #c7d2fe;
}

/* ── Sidebar ──────────────────────────────────────────────────── */
.adash-sidebar { display: flex; flex-direction: column; gap: 1rem; }
.adash-sidebar .adash-card:nth-child(1) { animation-delay: 80ms;  }
.adash-sidebar .adash-card:nth-child(2) { animation-delay: 140ms; }
.adash-sidebar .adash-card:nth-child(3) { animation-delay: 200ms; }

/* Acciones rápidas */
.adash-qaction {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.6rem 1.125rem;
    border-bottom: 1px solid #f1f5f9;
    text-decoration: none;
    color: #475569;
    font-size: 0.78rem;
    font-weight: 500;
    transition: color 130ms ease-out, background 130ms ease-out;
}
.adash-qaction:last-child { border-bottom: none; }
.adash-qaction:hover { color: #0f172a; background: #f8fafc; }
.adash-qaction:hover .adash-qaction-icon {
    background: #eef2ff;
    color: #4f46e5;
    border-color: #c7d2fe;
}
.adash-qaction-icon {
    width: 28px; height: 28px;
    border-radius: 7px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    display: flex; align-items: center; justify-content: center;
    color: #64748b; flex-shrink: 0;
    transition: background 130ms, color 130ms, border-color 130ms;
}
.adash-badge-sm {
    margin-left: auto;
    font-size: 0.62rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 10px;
}
.adash-badge-sm.danger  { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.adash-badge-sm.success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

/* Distribución de planes */
.adash-plan-row { padding: 0 1.125rem 0.75rem; }
.adash-plan-row:first-child { padding-top: 0.875rem; }
.adash-plan-row-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}
.adash-plan-name { font-size: 0.75rem; font-weight: 600; color: #475569; }
.adash-plan-num  { font-size: 0.8rem;  font-weight: 700; color: #0f172a; }
.adash-bar-bg    { height: 4px; background: #f1f5f9; border-radius: 2px; overflow: hidden; }
.adash-bar-fill  { height: 4px; border-radius: 2px; transition: width 600ms ease-out; }

/* Feed de actividad */
.adash-activity {
    display: flex;
    align-items: flex-start;
    gap: 0.625rem;
    padding: 0.625rem 1.125rem;
    border-bottom: 1px solid #f1f5f9;
}
.adash-activity:last-child { border-bottom: none; }
.adash-activity-icon {
    width: 24px; height: 24px;
    border-radius: 6px;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    margin-top: 1px;
}
.adash-activity-icon.error   { background: #fef2f2; color: #dc2626; }
.adash-activity-icon.warning { background: #fffbeb; color: #d97706; }
.adash-activity-icon.info    { background: #eef2ff; color: #4f46e5; }
.adash-activity-icon.default { background: #f8fafc; color: #64748b; }
.adash-activity-body  { flex: 1; min-width: 0; }
.adash-activity-text  { font-size: 0.73rem; color: #475569; line-height: 1.4; }
.adash-activity-text .empresa { font-weight: 600; color: #0f172a; }
.adash-activity-time  { font-size: 0.67rem; color: #94a3b8; margin-top: 2px; }

/* Estado vacío */
.adash-empty {
    text-align: center;
    padding: 2.5rem 1rem;
    color: #64748b;
    font-size: 0.78rem;
}

/* Badge plan mini para barra de estado */
.adash-plan-badge-sm {
    font-size: 0.68rem;
    font-weight: 700;
    padding: 2px 9px;
    border-radius: 5px;
}
</style>

<div class="adash">

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
         BARRA DE ESTADO
         Responde de inmediato: ¿el sistema está operacional?
    ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    <div class="adash-statusbar">
        <div class="adash-stat">
            <span class="adash-pulse {{ $sistemaOk ? 'ok' : 'error' }}"></span>
            <span class="lbl">Sistema</span>
            <span class="val {{ $sistemaOk ? 'ok' : 'error' }}">
                {{ $sistemaOk ? 'Operacional' : $kpis['incidentes'] . ' incidente(s)' }}
            </span>
        </div>
        <span class="sep">·</span>
        <div class="adash-stat">
            <span class="lbl">Empresas</span>
            <span class="val">{{ $kpis['activas'] }}<span style="color:#94a3b8;font-weight:400"> / {{ $kpis['total'] }}</span></span>
        </div>
        <span class="sep">·</span>
        <div class="adash-stat">
            <span class="adash-pulse {{ $kpis['online'] > 0 ? 'ok' : 'idle' }}"></span>
            <span class="lbl">Online</span>
            <span class="val {{ $kpis['online'] > 0 ? 'ok' : '' }}">{{ $kpis['online'] }} usuario(s)</span>
        </div>
        <span class="sep">·</span>
        <div class="adash-stat">
            <span class="lbl">Este mes</span>
            <span class="val {{ $kpis['nuevas'] > 0 ? 'warn' : '' }}">+{{ $kpis['nuevas'] }}</span>
        </div>
        {{-- Distribución de planes — extremo derecho --}}
        <div style="margin-left:auto; display:flex; gap:0.4rem; align-items:center;">
            @foreach ([
                'enterprise' => ['#92400e','#fffbeb','#fde68a'],
                'pro'        => ['#3730a3','#eef2ff','#c7d2fe'],
                'basic'      => ['#475569','#f8fafc','#e2e8f0'],
            ] as $plan => [$color, $bg, $border])
                @if (($kpis['porPlan'][$plan] ?? 0) > 0)
                    <span class="adash-plan-badge-sm"
                          style="background:{{ $bg }};color:{{ $color }};border:1px solid {{ $border }}">
                        {{ ucfirst($plan) }} {{ $kpis['porPlan'][$plan] }}
                    </span>
                @endif
            @endforeach
        </div>
    </div>

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
         KPI TILES — datos que MANDAN
         Número grande (700) → label (secundario) → contexto debajo
    ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    <div class="adash-kpis">

        {{-- Empresas activas --}}
        <div class="adash-kpi">
            <div class="adash-kpi-stripe" style="background:#6366f1;"></div>
            <span class="adash-kpi-label">Empresas activas</span>
            <span class="adash-kpi-value">{{ $kpis['activas'] }}</span>
            <span class="adash-kpi-sub">
                de {{ $kpis['total'] }} totales
                @if ($kpis['inactivas'] > 0)
                    · <span style="color:#dc2626">{{ $kpis['inactivas'] }} inactiva(s)</span>
                @endif
            </span>
        </div>

        {{-- Usuarios online --}}
        <div class="adash-kpi">
            <div class="adash-kpi-stripe" style="background:{{ $kpis['online'] > 0 ? '#22c55e' : '#e2e8f0' }};"></div>
            <span class="adash-kpi-label">Online ahora</span>
            <span class="adash-kpi-value {{ $kpis['online'] > 0 ? 'ok' : '' }}">{{ $kpis['online'] }}</span>
            <span class="adash-kpi-sub">
                {{ $kpis['online'] > 0
                    ? 'usuario(s) activos en los últimos 5 min'
                    : 'Sin sesiones activas en este momento' }}
            </span>
        </div>

        {{-- Incidentes --}}
        <div class="adash-kpi">
            <div class="adash-kpi-stripe" style="background:{{ $kpis['incidentes'] > 0 ? '#dc2626' : '#22c55e' }};"></div>
            <span class="adash-kpi-label">Incidentes abiertos</span>
            <span class="adash-kpi-value {{ $kpis['incidentes'] > 0 ? 'error' : 'ok' }}">{{ $kpis['incidentes'] }}</span>
            <span class="adash-kpi-sub">
                {{ $kpis['incidentes'] === 0
                    ? 'Sin errores sin resolver'
                    : 'Errores pendientes de resolución' }}
            </span>
        </div>

    </div>

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
         LAYOUT PRINCIPAL: MATRIZ + SIDEBAR
    ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    <div class="adash-main">

        {{-- ─────────────────────────────────────────────────────────
             MATRIZ EMPRESA × MÓDULO
             Herramienta operativa: qué módulos tiene cada empresa.
        ───────────────────────────────────────────────────────── --}}
        <div class="adash-card" style="animation-delay:40ms">
            <div class="adash-card-head">
                <span class="adash-card-title">Empresas · Módulos</span>
                <div class="adash-legend">
                    <span>
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#22c55e;"></span>
                        Activo
                    </span>
                    <span>
                        <span style="display:inline-block;width:8px;height:8px;border-radius:2px;background:#fef9c3;border:1px dashed #d97706;"></span>
                        Parcial
                    </span>
                    <span>
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#e2e8f0;"></span>
                        Inactivo
                    </span>
                </div>
            </div>

            @if ($empresas->isEmpty())
                <div class="adash-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" style="width:36px;height:36px;margin:0 auto 0.75rem;display:block;color:#cbd5e1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                    </svg>
                    Sin empresas registradas
                </div>
            @else
                <table class="adash-table">
                    <thead>
                        <tr>
                            <th class="col-company">Empresa</th>
                            @foreach ($modulos as $key => $cfg)
                                @php $mc = $moduloColors[$key] ?? ['dot' => '#64748b', 'bg' => '#f8fafc', 'border' => '#e2e8f0', 'label' => strtoupper(substr($key,0,3))]; @endphp
                                <th>
                                    <div class="adash-mod-head adash-tt">
                                        <span class="adash-mod-head-dot" style="background:{{ $mc['dot'] }}"></span>
                                        <span class="adash-mod-head-label">{{ $mc['label'] }}</span>
                                        <span class="tip">{{ $cfg['label'] }}</span>
                                    </div>
                                </th>
                            @endforeach
                            <th class="col-actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($empresas as $emp)
                            <tr>
                                {{-- Celda empresa --}}
                                <td class="col-company">
                                    <div class="adash-company-name">
                                        <span class="adash-online-dot {{ $emp->online ? 'yes' : 'no' }}"></span>
                                        {{ $emp->name }}
                                        @unless ($emp->activo)
                                            <span class="adash-inactive-tag">INACTIVA</span>
                                        @endunless
                                    </div>
                                    <div class="adash-company-meta">
                                        <span class="adash-plan {{ $emp->plan }}">{{ $emp->plan }}</span>
                                        <span class="adash-users-count">{{ $emp->users_count }} usr</span>
                                        <span class="adash-users-count" style="color:#94a3b8">{{ $emp->completados }}/{{ count($modulos) }}</span>
                                    </div>
                                </td>

                                {{-- Puntos de módulo --}}
                                @foreach ($emp->modulos as $key => $mod)
                                    @php
                                        $mc  = $moduloColors[$key] ?? ['dot' => '#64748b', 'bg' => '#f8fafc', 'border' => '#e2e8f0', 'label' => ''];
                                        $cls = match($mod['status']) { 'complete' => 'complete', 'partial' => 'partial', default => 'inactive' };
                                        $lbl = match($mod['status']) { 'complete' => 'Activo', 'partial' => 'Parcial', default => 'Inactivo' };
                                    @endphp
                                    <td>
                                        <div class="adash-tt">
                                            <span class="adash-mod-dot {{ $cls }}"
                                                  @if ($cls !== 'inactive')
                                                      style="background:{{ $mc['bg'] }};border-color:{{ $mc['border'] }}"
                                                  @endif>
                                                <span class="adash-mod-dot-inner"
                                                      style="background:{{ $mc['dot'] }}"></span>
                                            </span>
                                            <span class="tip">{{ $mod['label'] }}: {{ $lbl }}</span>
                                        </div>
                                    </td>
                                @endforeach

                                {{-- Gestionar módulos --}}
                                <td class="col-actions">
                                    <a href="{{ route('filament.admin.resources.empresas.features', ['record' => $emp->slug]) }}"
                                       class="adash-row-action adash-tt">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.764-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                        </svg>
                                        <span class="tip">Gestionar módulos</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- ─────────────────────────────────────────────────────────
             SIDEBAR
        ───────────────────────────────────────────────────────── --}}
        <div class="adash-sidebar">

            {{-- Acciones rápidas --}}
            <div class="adash-card">
                <div class="adash-card-head">
                    <span class="adash-card-title">Acciones rápidas</span>
                </div>
                <div class="adash-card-body">
                    <a href="{{ route('filament.admin.resources.empresas.create') }}" class="adash-qaction">
                        <span class="adash-qaction-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:13px;height:13px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </span>
                        Nueva empresa
                    </a>
                    <a href="{{ route('filament.admin.resources.empresas.index') }}" class="adash-qaction">
                        <span class="adash-qaction-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:13px;height:13px"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/></svg>
                        </span>
                        Ver todas las empresas
                    </a>
                    <a href="{{ route('filament.admin.resources.empresas.index', ['tableFilters[activo][value]' => '0']) }}" class="adash-qaction">
                        <span class="adash-qaction-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:13px;height:13px"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </span>
                        Empresas inactivas
                        @if ($kpis['inactivas'] > 0)
                            <span class="adash-badge-sm danger">{{ $kpis['inactivas'] }}</span>
                        @endif
                    </a>
                    <a href="{{ route('filament.admin.resources.service-invoices.index') }}" class="adash-qaction">
                        <span class="adash-qaction-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:13px;height:13px"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        </span>
                        Facturación
                    </a>
                    <a href="{{ route('filament.admin.pages.sesiones-activas-page') }}" class="adash-qaction">
                        <span class="adash-qaction-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:13px;height:13px"><path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 0 1 0-5.304m5.304 0a3.75 3.75 0 0 1 0 5.304m-7.425 2.121a6.75 6.75 0 0 1 0-9.546m9.546 0a6.75 6.75 0 0 1 0 9.546M5.106 18.894c-3.808-3.807-3.808-9.98 0-13.788m13.788 0c3.808 3.807 3.808 9.98 0 13.788M12 12h.008v.008H12V12Z"/></svg>
                        </span>
                        Sesiones activas
                        @if ($kpis['online'] > 0)
                            <span class="adash-badge-sm success">{{ $kpis['online'] }}</span>
                        @endif
                    </a>
                </div>
            </div>

            {{-- Distribución por plan --}}
            <div class="adash-card">
                <div class="adash-card-head">
                    <span class="adash-card-title">Por plan</span>
                    <span style="font-size:0.7rem;color:#64748b">{{ $kpis['activas'] }} activas</span>
                </div>
                <div class="adash-card-body" style="padding-top:0.125rem;">
                    @foreach ([
                        'enterprise' => ['Enterprise', '#d97706'],
                        'pro'        => ['Pro',        '#6366f1'],
                        'basic'      => ['Basic',      '#94a3b8'],
                    ] as $plan => [$label, $color])
                        @php
                            $count = $kpis['porPlan'][$plan] ?? 0;
                            $pct   = $kpis['activas'] > 0 ? round($count / $kpis['activas'] * 100) : 0;
                        @endphp
                        <div class="adash-plan-row">
                            <div class="adash-plan-row-top">
                                <span class="adash-plan-name">{{ $label }}</span>
                                <span class="adash-plan-num">{{ $count }}</span>
                            </div>
                            <div class="adash-bar-bg">
                                <div class="adash-bar-fill" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Feed de actividad reciente --}}
            <div class="adash-card">
                <div class="adash-card-head">
                    <span class="adash-card-title">Actividad reciente</span>
                    <a href="{{ route('filament.admin.resources.system-events.index') }}"
                       style="font-size:0.7rem;color:#64748b;text-decoration:none;font-weight:500;transition:color 130ms"
                       onmouseover="this.style.color='#4f46e5'" onmouseout="this.style.color='#64748b'">
                        Ver todo →
                    </a>
                </div>
                <div class="adash-card-body">
                    @forelse ($actividad as $evento)
                        <div class="adash-activity">
                            <span class="adash-activity-icon {{ in_array($evento->tipo ?? '', ['error','warning','info']) ? $evento->tipo : 'default' }}">
                                @if (($evento->tipo ?? '') === 'error')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:10px;height:10px"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14ZM8 5a.75.75 0 0 1 .75.75v2.5a.75.75 0 0 1-1.5 0v-2.5A.75.75 0 0 1 8 5Zm0 6a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                                @elseif (($evento->tipo ?? '') === 'warning')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:10px;height:10px"><path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 5a.75.75 0 0 1 .75.75v2.5a.75.75 0 0 1-1.5 0v-2.5A.75.75 0 0 1 8 5Zm0 6a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                                @elseif (($evento->tipo ?? '') === 'info')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:10px;height:10px"><path fill-rule="evenodd" d="M15 8A7 7 0 1 1 1 8a7 7 0 0 1 14 0ZM9 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM6.75 8a.75.75 0 0 0 0 1.5h.75v1.75a.75.75 0 0 0 1.5 0v-2.5A.75.75 0 0 0 8.25 8h-1.5Z" clip-rule="evenodd"/></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:10px;height:10px"><path fill-rule="evenodd" d="M1 8a7 7 0 1 1 14 0A7 7 0 0 1 1 8Zm7.75-4.25a.75.75 0 0 0-1.5 0V8c0 .414.336.75.75.75h3.25a.75.75 0 0 0 0-1.5h-2.5v-3.5Z" clip-rule="evenodd"/></svg>
                                @endif
                            </span>
                            <div class="adash-activity-body">
                                <div class="adash-activity-text">
                                    @if ($evento->empresa)
                                        <span class="empresa">{{ $evento->empresa->name }}</span> —
                                    @endif
                                    {{ \Illuminate\Support\Str::limit($evento->mensaje ?? $evento->titulo ?? '—', 54) }}
                                </div>
                                <div class="adash-activity-time">{{ $evento->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="adash-empty">Sin actividad reciente</div>
                    @endforelse
                </div>
            </div>

        </div>{{-- /sidebar --}}
    </div>{{-- /main --}}
</div>{{-- /adash --}}
</x-filament-panels::page>
