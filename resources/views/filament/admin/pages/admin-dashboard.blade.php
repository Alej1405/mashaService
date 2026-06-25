<x-filament-panels::page>
@php
    $kpis     = $this->getKpis();
    $empresas = $this->getEmpresasMatriz();
    $actividad = $this->getActividad();
    $modulos  = config('erp_features', []);
    $moduloKeys = array_keys($modulos);
@endphp

<style>
/* ── Dashboard custom styles ─────────────────────────── */
.dash-root { font-family: 'Sansation', system-ui, sans-serif; }

/* Status bar */
.dash-statusbar {
    display: flex; align-items: center; gap: 1.5rem;
    background: #0f172a; color: #e2e8f0;
    padding: 0.55rem 1.25rem; border-radius: 0.75rem;
    font-size: 0.78rem; font-weight: 500; letter-spacing: 0.01em;
    margin-bottom: 1.25rem; flex-wrap: wrap;
}
.dash-statusbar .sep { color: #334155; }
.dash-stat-item { display: flex; align-items: center; gap: 0.4rem; }
.dash-stat-item .label { color: #64748b; font-weight: 400; }
.dash-stat-item .val   { color: #f1f5f9; font-weight: 700; }
.dash-stat-item .val.green  { color: #34d399; }
.dash-stat-item .val.amber  { color: #fbbf24; }
.dash-stat-item .val.red    { color: #f87171; }
.dash-dot { width: 7px; height: 7px; border-radius: 50%; }
.dash-dot.green { background: #22c55e; box-shadow: 0 0 6px #22c55e80; animation: pulse 2s infinite; }
.dash-dot.red   { background: #ef4444; box-shadow: 0 0 6px #ef444480; }
.dash-dot.gray  { background: #475569; }
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }
@media (prefers-reduced-motion: reduce) { @keyframes pulse { from{} to{} } }

/* Main layout */
.dash-main { display: grid; grid-template-columns: 1fr 280px; gap: 1.25rem; align-items: start; }

/* Matrix section */
.dash-matrix-wrap {
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: 0.875rem; overflow: hidden;
}
.dash-matrix-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.875rem 1.25rem; border-bottom: 1px solid #f1f5f9;
    background: #fafafa;
}
.dash-matrix-title { font-size: 0.8rem; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.06em; }
.dash-matrix-legend { display: flex; align-items: center; gap: 1rem; font-size: 0.72rem; color: #64748b; }
.dash-matrix-legend span { display: flex; align-items: center; gap: 4px; }

/* Matrix table */
.dash-matrix { width: 100%; border-collapse: collapse; }
.dash-matrix thead th {
    padding: 0.5rem 0.375rem; text-align: center;
    font-size: 0.65rem; font-weight: 600; color: #94a3b8;
    text-transform: uppercase; letter-spacing: 0.04em;
    border-bottom: 1px solid #f1f5f9;
}
.dash-matrix thead th.company-col { text-align: left; padding-left: 1.25rem; min-width: 180px; }
.dash-matrix thead th.actions-col { min-width: 60px; }
.dash-matrix tbody tr {
    border-bottom: 1px solid #f8fafc;
    transition: background 120ms ease;
}
.dash-matrix tbody tr:last-child { border-bottom: none; }
.dash-matrix tbody tr:hover { background: #fffbeb; }
.dash-matrix td { padding: 0.625rem 0.375rem; vertical-align: middle; text-align: center; }
.dash-matrix td.company-col { text-align: left; padding-left: 1.25rem; }
.dash-matrix td.actions-col { padding-right: 1rem; }

/* Company cell */
.dash-company-name {
    font-size: 0.82rem; font-weight: 600; color: #0f172a;
    white-space: nowrap; display: flex; align-items: center; gap: 0.5rem;
}
.dash-company-meta { display: flex; align-items: center; gap: 0.4rem; margin-top: 2px; }
.dash-plan-badge {
    font-size: 0.62rem; font-weight: 700; padding: 1px 6px; border-radius: 4px;
    text-transform: uppercase; letter-spacing: 0.04em;
}
.dash-plan-badge.enterprise { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
.dash-plan-badge.pro        { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
.dash-plan-badge.basic      { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
.dash-users-count { font-size: 0.68rem; color: #94a3b8; }
.dash-inactive-badge { font-size: 0.62rem; color: #ef4444; font-weight: 600; }

/* Module dot */
.mod-dot {
    display: inline-flex; align-items: center; justify-content: center;
    width: 22px; height: 22px; border-radius: 50%; cursor: default;
    transition: transform 100ms ease;
    position: relative;
}
.mod-dot:hover { transform: scale(1.2); }
.mod-dot.complete { background: #dcfce7; color: #16a34a; }
.mod-dot.partial  { background: #fef9c3; color: #b45309; }
.mod-dot.inactive { background: #f1f5f9; color: #cbd5e1; }
.mod-dot svg { width: 11px; height: 11px; }

/* Tooltip */
.mod-dot .tooltip {
    display: none; position: absolute; bottom: calc(100% + 6px); left: 50%;
    transform: translateX(-50%); background: #0f172a; color: #f1f5f9;
    font-size: 0.67rem; white-space: nowrap; padding: 3px 8px; border-radius: 5px;
    pointer-events: none; z-index: 50;
}
.mod-dot:hover .tooltip { display: block; }
.mod-dot .tooltip::after {
    content: ''; position: absolute; top: 100%; left: 50%;
    transform: translateX(-50%); border: 4px solid transparent;
    border-top-color: #0f172a;
}

/* Action link */
.dash-action-link {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 6px; color: #94a3b8;
    transition: background 120ms, color 120ms;
}
.dash-action-link:hover { background: #fef3c7; color: #b45309; }

/* Online indicator */
.online-dot {
    width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.online-dot.yes { background: #22c55e; animation: pulse 2s infinite; }
.online-dot.no  { background: #e2e8f0; }

/* ── Sidebar ──────────────────────────────────────────── */
.dash-sidebar { display: flex; flex-direction: column; gap: 1.25rem; }

/* Plan distribution */
.dash-card {
    background: #fff; border: 1px solid #e2e8f0;
    border-radius: 0.875rem; overflow: hidden;
}
.dash-card-header {
    padding: 0.75rem 1.1rem; border-bottom: 1px solid #f1f5f9;
    font-size: 0.75rem; font-weight: 700; color: #0f172a;
    text-transform: uppercase; letter-spacing: 0.06em;
    background: #fafafa;
}
.dash-card-body { padding: 0.875rem 1.1rem; }

/* Plan bars */
.plan-row { margin-bottom: 0.625rem; }
.plan-row:last-child { margin-bottom: 0; }
.plan-row-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
.plan-row-label { font-size: 0.75rem; font-weight: 600; color: #374151; }
.plan-row-count { font-size: 0.8rem; font-weight: 700; color: #0f172a; }
.plan-bar-bg { height: 4px; background: #f1f5f9; border-radius: 2px; overflow: hidden; }
.plan-bar-fill { height: 4px; border-radius: 2px; transition: width 600ms ease; }

/* Activity feed */
.activity-item {
    display: flex; align-items: flex-start; gap: 0.625rem;
    padding: 0.5rem 0; border-bottom: 1px solid #f8fafc;
    font-size: 0.75rem;
}
.activity-item:last-child { border-bottom: none; padding-bottom: 0; }
.activity-icon {
    width: 24px; height: 24px; border-radius: 6px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.activity-icon.error   { background: #fee2e2; color: #b91c1c; }
.activity-icon.warning { background: #fef9c3; color: #92400e; }
.activity-icon.info    { background: #f0fdf4; color: #166534; }
.activity-icon.default { background: #f8fafc; color: #64748b; }
.activity-text { color: #374151; line-height: 1.35; flex: 1; }
.activity-text .empresa { font-weight: 600; color: #0f172a; }
.activity-time { color: #94a3b8; font-size: 0.68rem; white-space: nowrap; margin-top: 1px; }

/* Quick actions */
.qaction {
    display: flex; align-items: center; gap: 0.6rem; padding: 0.5rem 0;
    border-bottom: 1px solid #f8fafc; text-decoration: none;
    color: #374151; font-size: 0.78rem; font-weight: 500;
    transition: color 120ms;
}
.qaction:last-child { border-bottom: none; padding-bottom: 0; }
.qaction:hover { color: #b45309; }
.qaction-icon {
    width: 28px; height: 28px; border-radius: 7px; background: #f8fafc;
    display: flex; align-items: center; justify-content: center;
    color: #94a3b8; flex-shrink: 0; transition: background 120ms, color 120ms;
}
.qaction:hover .qaction-icon { background: #fef3c7; color: #b45309; }

/* Empty state */
.empty-state { text-align: center; padding: 2.5rem 1rem; color: #94a3b8; font-size: 0.8rem; }

/* Column labels tooltips (module headers) */
.mod-header { cursor: default; position: relative; }
.mod-header .header-tooltip {
    display: none; position: absolute; top: calc(100% + 4px); left: 50%;
    transform: translateX(-50%); background: #0f172a; color: #f1f5f9;
    font-size: 0.65rem; white-space: nowrap; padding: 2px 6px; border-radius: 4px;
    z-index: 50; pointer-events: none;
}
.mod-header:hover .header-tooltip { display: block; }
</style>

<div class="dash-root">

    {{-- ── STATUS BAR ─────────────────────────────────────── --}}
    <div class="dash-statusbar">
        <div class="dash-stat-item">
            <span class="dash-dot {{ $kpis['incidentes'] > 0 ? 'red' : 'green' }}"></span>
            <span class="label">Estado</span>
            <span class="val {{ $kpis['incidentes'] > 0 ? 'red' : 'green' }}">
                {{ $kpis['incidentes'] > 0 ? $kpis['incidentes'] . ' incidente(s)' : 'Operacional' }}
            </span>
        </div>
        <span class="sep">·</span>
        <div class="dash-stat-item">
            <span class="label">Empresas</span>
            <span class="val">{{ $kpis['activas'] }}<span style="color:#475569;font-weight:400">/{{ $kpis['total'] }}</span></span>
        </div>
        <span class="sep">·</span>
        <div class="dash-stat-item">
            <span class="dash-dot {{ $kpis['online'] > 0 ? 'green' : 'gray' }}"></span>
            <span class="label">Online</span>
            <span class="val {{ $kpis['online'] > 0 ? 'green' : '' }}">{{ $kpis['online'] }} usuario(s)</span>
        </div>
        <span class="sep">·</span>
        <div class="dash-stat-item">
            <span class="label">Este mes</span>
            <span class="val {{ $kpis['nuevas'] > 0 ? 'amber' : '' }}">
                +{{ $kpis['nuevas'] }} empresa(s)
            </span>
        </div>
        <div style="margin-left:auto;display:flex;gap:0.5rem;">
            @foreach (['enterprise' => '#92400e', 'pro' => '#1e40af', 'basic' => '#475569'] as $plan => $color)
                @if (($kpis['porPlan'][$plan] ?? 0) > 0)
                    <span style="font-size:0.7rem;background:rgba(255,255,255,0.07);padding:2px 8px;border-radius:4px;color:#94a3b8;">
                        {{ ucfirst($plan) }} <strong style="color:#f1f5f9">{{ $kpis['porPlan'][$plan] }}</strong>
                    </span>
                @endif
            @endforeach
        </div>
    </div>

    {{-- ── MAIN GRID ───────────────────────────────────────── --}}
    <div class="dash-main">

        {{-- ── EMPRESA × MÓDULO MATRIX ─────────────────────── --}}
        <div class="dash-matrix-wrap">
            <div class="dash-matrix-header">
                <span class="dash-matrix-title">Matriz de servicios</span>
                <div class="dash-matrix-legend">
                    <span><span style="color:#16a34a;font-size:14px">●</span> Activo</span>
                    <span><span style="color:#b45309;font-size:14px">◑</span> Parcial</span>
                    <span><span style="color:#cbd5e1;font-size:14px">○</span> Inactivo</span>
                </div>
            </div>

            @if ($empresas->isEmpty())
                <div class="empty-state">Sin empresas registradas</div>
            @else
                <table class="dash-matrix">
                    <thead>
                        <tr>
                            <th class="company-col">Empresa</th>
                            @foreach ($modulos as $key => $cfg)
                                <th class="mod-header">
                                    {{ strtoupper(substr($cfg['label'], 0, 3)) }}
                                    <div class="header-tooltip">{{ $cfg['label'] }}</div>
                                </th>
                            @endforeach
                            <th class="actions-col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($empresas as $emp)
                            <tr>
                                <td class="company-col">
                                    <div class="dash-company-name">
                                        <span class="online-dot {{ $emp->online ? 'yes' : 'no' }}"></span>
                                        <span>{{ $emp->name }}</span>
                                        @unless ($emp->activo)
                                            <span class="dash-inactive-badge">INACTIVA</span>
                                        @endunless
                                    </div>
                                    <div class="dash-company-meta">
                                        <span class="dash-plan-badge {{ $emp->plan }}">{{ $emp->plan }}</span>
                                        <span class="dash-users-count">{{ $emp->users_count }} usuario(s)</span>
                                        <span class="dash-users-count">{{ $emp->completados }}/{{ count($modulos) }}</span>
                                    </div>
                                </td>

                                @foreach ($emp->modulos as $key => $mod)
                                    @php
                                        $cls = match($mod['status']) {
                                            'complete' => 'complete',
                                            'partial'  => 'partial',
                                            default    => 'inactive',
                                        };
                                        $sym = match($mod['status']) {
                                            'complete' => '●',
                                            'partial'  => '◑',
                                            default    => '○',
                                        };
                                    @endphp
                                    <td>
                                        <span class="mod-dot {{ $cls }}">
                                            {{ $sym }}
                                            <span class="tooltip">{{ $mod['label'] }}: {{ $mod['status'] === 'complete' ? 'activo' : ($mod['status'] === 'partial' ? 'parcial' : 'inactivo') }}</span>
                                        </span>
                                    </td>
                                @endforeach

                                <td class="actions-col">
                                    <a href="{{ route('filament.admin.resources.servicios-empresas.features', ['record' => $emp->slug]) }}"
                                       class="dash-action-link" title="Gestionar módulos">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:15px;height:15px">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.764-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- ── SIDEBAR ──────────────────────────────────────── --}}
        <div class="dash-sidebar">

            {{-- Acciones rápidas --}}
            <div class="dash-card">
                <div class="dash-card-header">Acciones rápidas</div>
                <div class="dash-card-body" style="padding-top:0.5rem;padding-bottom:0.5rem;">
                    <a href="{{ route('filament.admin.resources.servicios-empresas.create') }}" class="qaction">
                        <span class="qaction-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </span>
                        Nueva empresa
                    </a>
                    <a href="{{ route('filament.admin.resources.servicios-empresas.index') }}" class="qaction">
                        <span class="qaction-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z"/></svg>
                        </span>
                        Ver todas las empresas
                    </a>
                    <a href="{{ route('filament.admin.resources.servicios-empresas.index', ['tableFilters[activo][value]' => '0']) }}" class="qaction">
                        <span class="qaction-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </span>
                        Empresas inactivas
                        @if ($kpis['inactivas'] > 0)
                            <span style="margin-left:auto;background:#fee2e2;color:#b91c1c;font-size:0.65rem;font-weight:700;padding:1px 6px;border-radius:10px;">{{ $kpis['inactivas'] }}</span>
                        @endif
                    </a>
                    <a href="{{ route('filament.admin.resources.service-invoices.index') }}" class="qaction">
                        <span class="qaction-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        </span>
                        Facturación del mes
                    </a>
                    <a href="{{ route('filament.admin.pages.sesiones-activas-page') }}" class="qaction">
                        <span class="qaction-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 0 1 0-5.304m5.304 0a3.75 3.75 0 0 1 0 5.304m-7.425 2.121a6.75 6.75 0 0 1 0-9.546m9.546 0a6.75 6.75 0 0 1 0 9.546M5.106 18.894c-3.808-3.807-3.808-9.98 0-13.788m13.788 0c3.808 3.807 3.808 9.98 0 13.788M12 12h.008v.008H12V12Z"/></svg>
                        </span>
                        Sesiones activas
                        @if ($kpis['online'] > 0)
                            <span style="margin-left:auto;background:#dcfce7;color:#166534;font-size:0.65rem;font-weight:700;padding:1px 6px;border-radius:10px;">{{ $kpis['online'] }}</span>
                        @endif
                    </a>
                </div>
            </div>

            {{-- Distribución por plan --}}
            <div class="dash-card">
                <div class="dash-card-header">Distribución por plan</div>
                <div class="dash-card-body">
                    @foreach ([
                        'enterprise' => ['Enterprise', '#f59e0b'],
                        'pro'        => ['Pro',        '#3b82f6'],
                        'basic'      => ['Basic',      '#94a3b8'],
                    ] as $plan => [$label, $color])
                        @php
                            $count = $kpis['porPlan'][$plan] ?? 0;
                            $pct   = $kpis['activas'] > 0 ? round($count / $kpis['activas'] * 100) : 0;
                        @endphp
                        <div class="plan-row">
                            <div class="plan-row-top">
                                <span class="plan-row-label">{{ $label }}</span>
                                <span class="plan-row-count">{{ $count }}</span>
                            </div>
                            <div class="plan-bar-bg">
                                <div class="plan-bar-fill" style="width:{{ $pct }}%;background:{{ $color }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Feed de actividad --}}
            <div class="dash-card">
                <div class="dash-card-header">Actividad reciente</div>
                <div class="dash-card-body" style="padding-top:0.25rem;padding-bottom:0.25rem;">
                    @forelse ($actividad as $evento)
                        <div class="activity-item">
                            <span class="activity-icon {{ in_array($evento->tipo, ['error','warning','info']) ? $evento->tipo : 'default' }}">
                                @if ($evento->tipo === 'error')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:10px;height:10px"><path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14ZM8 5a.75.75 0 0 1 .75.75v2.5a.75.75 0 0 1-1.5 0v-2.5A.75.75 0 0 1 8 5Zm0 6a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                                @elseif ($evento->tipo === 'warning')
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:10px;height:10px"><path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 5a.75.75 0 0 1 .75.75v2.5a.75.75 0 0 1-1.5 0v-2.5A.75.75 0 0 1 8 5Zm0 6a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:10px;height:10px"><path fill-rule="evenodd" d="M15 8A7 7 0 1 1 1 8a7 7 0 0 1 14 0ZM9 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM6.75 8a.75.75 0 0 0 0 1.5h.75v1.75a.75.75 0 0 0 1.5 0v-2.5A.75.75 0 0 0 8.25 8h-1.5Z" clip-rule="evenodd"/></svg>
                                @endif
                            </span>
                            <div style="flex:1;min-width:0;">
                                <div class="activity-text">
                                    @if ($evento->empresa)
                                        <span class="empresa">{{ $evento->empresa->name }}</span> —
                                    @endif
                                    {{ \Illuminate\Support\Str::limit($evento->mensaje, 55) }}
                                </div>
                                <div class="activity-time">{{ $evento->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state" style="padding:1.5rem 0;">Sin actividad reciente</div>
                    @endforelse
                </div>
            </div>

        </div>{{-- /sidebar --}}
    </div>{{-- /main --}}
</div>{{-- /dash-root --}}
</x-filament-panels::page>
