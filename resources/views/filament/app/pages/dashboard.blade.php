<x-filament-panels::page>
<style>
/* Dashboard operativo - Light mode (mismo sistema que Ecommerce/hub) */
.dash {
    --accent:   #4f46e5;
    --accent-2: #4338ca;
    --accent-soft: rgba(79,70,229,0.08);
    --surface:  #ffffff;
    --surface-2:#f8fafc;
    --border:   #e2e8f0;
    --border-2: #cbd5e1;
    --text:     #0f172a;
    --muted:    #64748b;
    --muted-2:  #94a3b8;
    --ok:       #059669;
    --ok-soft:  rgba(5,150,105,0.08);
    --warn:     #b45309;
    --warn-soft:rgba(180,83,9,0.08);
    --warn-border:rgba(180,83,9,0.22);
    --danger:   #be123c;
    --danger-soft: rgba(190,18,60,0.08);
    --ease:     cubic-bezier(0.23, 1, 0.32, 1);
    --r:        14px;
    --r-sm:     10px;
    --shadow-sm:0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
    --shadow:   0 6px 20px rgba(15,23,42,0.08), 0 1px 3px rgba(0,0,0,0.04);
    font-family: 'Sansation', system-ui, sans-serif;
    animation: dash-in 240ms var(--ease) both;
    max-width: 1120px;
}
@keyframes dash-in { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }
@keyframes dash-row { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:translateY(0); } }

/* Header */
.dash-head { display:flex; align-items:center; gap:16px; margin-bottom:1.75rem; flex-wrap:wrap; }
.dash-avatar { flex:0 0 auto; width:54px; height:54px; border-radius:14px; display:grid; place-items:center;
    background:var(--accent-soft); color:var(--accent-2); font-weight:700; font-size:1.35rem; border:1px solid var(--border); overflow:hidden; }
.dash-avatar img { width:100%; height:100%; object-fit:cover; }
.dash-headtext { flex:1 1 auto; min-width:0; }
.dash-greeting { font-size:clamp(1.4rem, 1.15rem + 1.1vw, 1.85rem); font-weight:700; color:var(--text); letter-spacing:-0.02em; margin:0; line-height:1.15; text-wrap:balance; }
.dash-sub { margin:4px 0 0; font-size:0.875rem; color:var(--muted); }
.dash-plan { flex:0 0 auto; align-self:center; font-size:0.78rem; font-weight:600; color:var(--accent-2);
    background:var(--accent-soft); border:1px solid #e0e7ff; padding:5px 12px; border-radius:9999px; }

/* Metrics */
.dash-metrics { display:grid; grid-template-columns:repeat(2,1fr); gap:12px; }
@media (min-width:680px){ .dash-metrics { grid-template-columns:repeat(4,1fr); } }
.dash-metric { display:flex; flex-direction:column; gap:2px; padding:16px 18px; background:var(--surface);
    border:1px solid var(--border); border-radius:var(--r-sm); box-shadow:var(--shadow-sm); text-decoration:none;
    transition:border-color 160ms var(--ease), box-shadow 160ms var(--ease), transform 120ms var(--ease); }
.dash-metric-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; }
.dash-metric-ico { width:34px; height:34px; border-radius:9px; display:grid; place-items:center; color:var(--accent);
    background:color-mix(in srgb, var(--accent) 12%, #fff); }
.dash-metric-ico svg { width:18px; height:18px; }
.dash-metric-value { font-size:1.6rem; font-weight:800; color:var(--text); line-height:1.05; letter-spacing:-0.03em; font-variant-numeric:tabular-nums; }
.dash-metric-label { font-size:0.72rem; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:0.06em; margin-top:2px; }
.dash-metric-sub { font-size:0.72rem; color:var(--muted-2); margin-top:3px; }
@media (hover:hover) and (pointer:fine){ .dash-metric:hover { border-color:var(--border-2); box-shadow:var(--shadow); transform:translateY(-2px); } }
.dash-metric:active { transform:scale(0.985); }
.dash-metric.is-warn { border-color:var(--warn-border); background:var(--warn-soft); }
.dash-metric.is-warn .dash-metric-value { color:var(--warn); }
.dash-metric.is-warn .dash-metric-ico { color:var(--warn); background:var(--warn-soft); }
.dash-metric.is-warn .dash-metric-sub { color:var(--warn); opacity:0.75; }

/* Main grid */
.dash-main { display:grid; grid-template-columns:1fr; gap:14px; margin-top:14px; }
@media (min-width:900px){ .dash-main { grid-template-columns:1fr 300px; } }

.dash-panel { background:var(--surface); border:1px solid var(--border); border-radius:var(--r); box-shadow:var(--shadow-sm); overflow:hidden; }
.dash-panel-head { display:flex; align-items:center; justify-content:space-between; padding:13px 18px; background:var(--surface-2); border-bottom:1px solid var(--border); }
.dash-panel-title { font-size:0.7rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--muted); }
.dash-panel-link { font-size:0.72rem; font-weight:600; color:var(--accent-2); text-decoration:none; transition:opacity 140ms var(--ease); }
@media (hover:hover){ .dash-panel-link:hover { opacity:0.7; } }

/* Stock rows */
.dash-srow { display:flex; align-items:center; gap:12px; padding:12px 18px; border-bottom:1px solid var(--border); animation:dash-row 280ms var(--ease) both; animation-delay:calc(var(--i,0) * 45ms + 60ms); }
.dash-srow:last-child { border-bottom:none; }
.dash-srow-name { flex:1 1 auto; min-width:0; font-size:0.9rem; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.dash-srow-min { font-size:0.72rem; color:var(--muted); margin-top:1px; }
.dash-badge { flex:0 0 auto; font-size:0.74rem; font-weight:700; padding:4px 10px; border-radius:9999px; font-variant-numeric:tabular-nums; white-space:nowrap; }
.dash-badge.crit { background:var(--danger-soft); color:var(--danger); border:1px solid rgba(190,18,60,0.22); }
.dash-badge.low  { background:var(--warn-soft);   color:var(--warn);   border:1px solid var(--warn-border); }

/* Empty state */
.dash-empty { display:flex; flex-direction:column; align-items:center; text-align:center; padding:34px 20px; }
.dash-empty-ico { width:44px; height:44px; border-radius:12px; display:grid; place-items:center; background:var(--ok-soft); color:var(--ok); margin-bottom:12px; }
.dash-empty-ico svg { width:24px; height:24px; }
.dash-empty-title { font-size:0.9rem; font-weight:600; color:var(--text); }
.dash-empty-desc { font-size:0.8rem; color:var(--muted); margin-top:2px; }

/* Quick links */
.dash-links { display:flex; flex-direction:column; gap:10px; }
.dash-link { display:flex; align-items:center; gap:13px; padding:14px 16px; background:var(--surface); border:1px solid var(--border); border-radius:var(--r-sm); box-shadow:var(--shadow-sm); text-decoration:none; transition:border-color 160ms var(--ease), box-shadow 160ms var(--ease), transform 120ms var(--ease); }
.dash-link-ico { flex:0 0 auto; width:40px; height:40px; border-radius:10px; display:grid; place-items:center; color:var(--accent); background:color-mix(in srgb, var(--accent) 12%, #fff); }
.dash-link-ico svg { width:20px; height:20px; }
.dash-link-body { flex:1 1 auto; min-width:0; }
.dash-link-title { font-size:0.93rem; font-weight:600; color:var(--text); }
.dash-link-sub { font-size:0.76rem; color:var(--muted); margin-top:1px; }
.dash-link-arrow { flex:0 0 auto; color:#cbd5e1; transition:transform 160ms var(--ease), color 160ms ease-out; }
.dash-link-arrow svg { width:18px; height:18px; }
@media (hover:hover) and (pointer:fine){
    .dash-link:hover { border-color:var(--accent); box-shadow:var(--shadow); transform:translateY(-2px); }
    .dash-link:hover .dash-link-arrow { color:var(--accent); transform:translateX(3px); }
}
/* Press feedback (release más snappy que el hover) */
.dash-link:active { transform:scale(0.98); transition-duration:80ms; }
/* Foco por teclado */
.dash-metric:focus-visible, .dash-link:focus-visible, .dash-panel-link:focus-visible { outline:2px solid var(--accent); outline-offset:2px; border-radius:var(--r-sm); }
@media (prefers-reduced-motion:reduce){ .dash, .dash-srow, .dash-metric, .dash-link { animation:none; transition:none; } }
</style>

@php
    $metrics = [
        ['label'=>'Productos','value'=>number_format($productosPub),'url'=>$urlProductos,
         'sub'=> $productosPub === $productosTotal ? 'todos publicados' : number_format(max($productosTotal-$productosPub,0)).' sin publicar',
         'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3 3.75 7.5m16.5 0L12 12m8.25-4.5v9L12 21m0-9L3.75 7.5m0 0v9L12 21"/>'],
        ['label'=>'Insumos','value'=>number_format($insumos),'url'=>$urlInventario,'sub'=>'materia prima e insumos',
         'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.3 24.3 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.066 9.066 0 0 0-6.23-.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/>'],
        ['label'=>'Clientes','value'=>number_format($clientes),'url'=>$urlClientes,'sub'=>'registrados',
         'icon'=>'<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21 12.317 12.317 0 0 1 2.25 19.234v-.106c0-1.113.285-2.16.786-3.07M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/>'],
    ];
@endphp

<div class="dash">
    {{-- Header --}}
    <header class="dash-head">
        <div class="dash-avatar">
            @if ($logo)<img src="{{ $logo }}" alt="{{ $empresa->name }}">@else{{ $inicial }}@endif
        </div>
        <div class="dash-headtext">
            <h1 class="dash-greeting">{{ $saludo }}</h1>
            <p class="dash-sub">{{ $empresa->name }} · {{ $fecha }}</p>
        </div>
        <span class="dash-plan">{{ $plan }}</span>
    </header>

    {{-- Metrics --}}
    <div class="dash-metrics">
        @foreach ($metrics as $m)
            <a href="{{ $m['url'] }}" class="dash-metric">
                <div class="dash-metric-top">
                    <span class="dash-metric-ico"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">{!! $m['icon'] !!}</svg></span>
                </div>
                <span class="dash-metric-value">{{ $m['value'] }}</span>
                <span class="dash-metric-label">{{ $m['label'] }}</span>
                <span class="dash-metric-sub">{{ $m['sub'] }}</span>
            </a>
        @endforeach

        <a href="{{ $urlInventario }}" class="dash-metric {{ $alertas > 0 ? 'is-warn' : '' }}">
            <div class="dash-metric-top">
                <span class="dash-metric-ico">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
                        @if ($alertas > 0)
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        @endif
                    </svg>
                </span>
            </div>
            <span class="dash-metric-value">{{ number_format($alertas) }}</span>
            <span class="dash-metric-label">Alertas de stock</span>
            <span class="dash-metric-sub">{{ $alertas > 0 ? 'insumos por agotarse' : 'todo en nivel' }}</span>
        </a>
    </div>

    {{-- Main grid --}}
    <div class="dash-main">
        {{-- Stock por agotarse --}}
        <section class="dash-panel">
            <div class="dash-panel-head">
                <span class="dash-panel-title">Stock por agotarse</span>
                <a href="{{ $urlInventario }}" class="dash-panel-link">Ver inventario →</a>
            </div>

            @forelse ($stockBajo as $i => $item)
                @php
                    $um    = $item->measurementUnit?->abreviatura ?? '';
                    $stock = rtrim(rtrim(number_format((float) $item->stock_actual, 4, '.', ''), '0'), '.');
                    $min   = rtrim(rtrim(number_format((float) $item->stock_minimo, 4, '.', ''), '0'), '.');
                    $crit  = (float) $item->stock_actual <= 0;
                @endphp
                <div class="dash-srow" style="--i: {{ $i }}">
                    <div style="flex:1 1 auto; min-width:0;">
                        <div class="dash-srow-name">{{ $item->nombre }}</div>
                        <div class="dash-srow-min">mínimo: {{ $min }} {{ $um }}</div>
                    </div>
                    <span class="dash-badge {{ $crit ? 'crit' : 'low' }}">{{ $stock }} {{ $um }}</span>
                </div>
            @empty
                <div class="dash-empty">
                    <span class="dash-empty-ico">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </span>
                    <div class="dash-empty-title">Todo en nivel</div>
                    <div class="dash-empty-desc">Ningún insumo por debajo de su mínimo.</div>
                </div>
            @endforelse
        </section>

        {{-- Accesos rápidos --}}
        <aside class="dash-links">
            <a href="{{ $urlProductos }}" class="dash-link">
                <span class="dash-link-ico"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3 3.75 7.5m16.5 0L12 12m8.25-4.5v9L12 21m0-9L3.75 7.5m0 0v9L12 21"/></svg></span>
                <span class="dash-link-body"><span class="dash-link-title">Productos</span><span class="dash-link-sub">Catálogo y publicación</span></span>
                <span class="dash-link-arrow"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg></span>
            </a>
            <a href="{{ $urlInventario }}" class="dash-link">
                <span class="dash-link-ico"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg></span>
                <span class="dash-link-body"><span class="dash-link-title">Inventario</span><span class="dash-link-sub">Insumos, almacenes, unidades</span></span>
                <span class="dash-link-arrow"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg></span>
            </a>
            <a href="{{ $urlClientes }}" class="dash-link">
                <span class="dash-link-ico"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21 12.317 12.317 0 0 1 2.25 19.234v-.106c0-1.113.285-2.16.786-3.07M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/></svg></span>
                <span class="dash-link-body"><span class="dash-link-title">Clientes</span><span class="dash-link-sub">Directorio de la empresa</span></span>
                <span class="dash-link-arrow"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg></span>
            </a>
        </aside>
    </div>
</div>
</x-filament-panels::page>
