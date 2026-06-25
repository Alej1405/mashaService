<x-filament-panels::page>
<style>
/* ── Ecommerce Dashboard — Light mode ──────────────────────── */
.eco-dash {
    --v:        #7c3aed;
    --v-soft:   rgba(124, 58, 237, 0.08);
    --v-border: rgba(124, 58, 237, 0.22);
    --v-text:   #6d28d9;
    --surface:  #ffffff;
    --surface-2:#f8fafc;
    --surface-3:#f1f5f9;
    --border:   #e2e8f0;
    --border-2: #cbd5e1;
    --text:     #0f172a;
    --text-2:   #1e293b;
    --muted:    #64748b;
    --muted-2:  #94a3b8;
    --ok:       #059669;
    --ok-soft:  rgba(5, 150, 105, 0.08);
    --ok-border:rgba(5, 150, 105, 0.22);
    --warn:     #b45309;
    --warn-soft:rgba(180, 83, 9, 0.08);
    --warn-border:rgba(180, 83, 9, 0.22);
    --danger:   #be123c;
    --ease:     cubic-bezier(0.23, 1, 0.32, 1);
    --r:        12px;
    --r-sm:     8px;
    --shadow-sm:0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
    --shadow:   0 4px 12px rgba(0,0,0,0.08), 0 1px 3px rgba(0,0,0,0.04);
    font-family: 'Sansation', system-ui, sans-serif;
    animation: eco-in 240ms var(--ease) both;
}

@keyframes eco-in {
    from { opacity: 0; transform: translateY(5px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Metrics row ───────────────────────────────────────────── */
.eco-metrics {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}
@media (min-width: 640px) {
    .eco-metrics { grid-template-columns: repeat(4, 1fr); }
}

.eco-metric {
    display: flex;
    flex-direction: column;
    padding: 14px 16px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r-sm);
    box-shadow: var(--shadow-sm);
    text-decoration: none;
    transition: border-color 160ms var(--ease), box-shadow 160ms var(--ease),
                transform 120ms var(--ease);
    gap: 2px;
}
@media (hover: hover) and (pointer: fine) {
    .eco-metric:hover {
        border-color: var(--border-2);
        box-shadow: var(--shadow);
    }
}
.eco-metric:active { transform: scale(0.98); transition-duration: 80ms; }
.eco-metric:focus-visible { outline: 2px solid var(--v); outline-offset: 2px; }

.eco-metric.is-warn {
    border-color: var(--warn-border);
    background: var(--warn-soft);
}
@media (hover: hover) and (pointer: fine) {
    .eco-metric.is-warn:hover { border-color: var(--warn); }
}

.eco-metric-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--text);
    line-height: 1.1;
    letter-spacing: -0.03em;
}
.eco-metric.is-warn .eco-metric-value { color: var(--warn); }
.eco-metric-label {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-top: 1px;
}
.eco-metric-sub {
    font-size: 0.68rem;
    color: var(--muted-2);
    margin-top: 3px;
}
.eco-metric.is-warn .eco-metric-sub { color: var(--warn); opacity: 0.7; }

/* ── Main grid ─────────────────────────────────────────────── */
.eco-main-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 14px;
    margin-top: 14px;
}
@media (min-width: 900px) {
    .eco-main-grid { grid-template-columns: 1fr 288px; }
}

/* ── Panel base ────────────────────────────────────────────── */
.eco-panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
}
.eco-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    background: var(--surface-2);
    border-bottom: 1px solid var(--border);
}
.eco-panel-title {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--muted);
}
.eco-panel-link {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--v-text);
    text-decoration: none;
    transition: opacity 140ms var(--ease);
}
@media (hover: hover) and (pointer: fine) {
    .eco-panel-link:hover { opacity: 0.7; }
}
.eco-panel-link:focus-visible { outline: 2px solid var(--v); outline-offset: 2px; border-radius: 2px; }

/* ── Orders list ───────────────────────────────────────────── */
.eco-order-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 18px;
    border-bottom: 1px solid var(--border);
    text-decoration: none;
    transition: background 140ms var(--ease);
    animation: row-in 280ms var(--ease) both;
    animation-delay: calc(var(--i, 0) * 45ms + 60ms);
}
.eco-order-row:last-child { border-bottom: none; }
@media (hover: hover) and (pointer: fine) {
    .eco-order-row:hover { background: var(--surface-2); }
}
.eco-order-row:active    { background: var(--surface-3); transition-duration: 80ms; }
.eco-order-row:focus-visible { outline: 2px solid var(--v); outline-offset: -2px; border-radius: 2px; }

@keyframes row-in {
    from { opacity: 0; transform: translateX(-4px); }
    to   { opacity: 1; transform: translateX(0); }
}

.eco-order-num {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--muted);
    font-family: ui-monospace, 'SF Mono', monospace;
    flex-shrink: 0;
    min-width: 90px;
}
.eco-order-customer {
    flex: 1;
    min-width: 0;
}
.eco-order-name {
    font-size: 0.82rem;
    font-weight: 500;
    color: var(--text-2);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.eco-order-time {
    font-size: 0.68rem;
    color: var(--muted-2);
    margin-top: 1px;
}
.eco-order-amount {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--text);
    flex-shrink: 0;
    text-align: right;
    min-width: 58px;
}
.eco-order-badge {
    font-size: 0.62rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 20px;
    letter-spacing: 0.03em;
    white-space: nowrap;
    flex-shrink: 0;
}
.eco-badge-pendiente  { background: var(--warn-soft);  color: var(--warn);   border: 1px solid var(--warn-border); }
.eco-badge-pagado     { background: rgba(3,105,161,0.08); color: #0369a1;    border: 1px solid rgba(3,105,161,0.22); }
.eco-badge-procesando { background: var(--v-soft);     color: var(--v-text); border: 1px solid var(--v-border); }
.eco-badge-enviado    { background: rgba(3,105,161,0.08); color: #0369a1;    border: 1px solid rgba(3,105,161,0.22); }
.eco-badge-entregado  { background: var(--ok-soft);    color: var(--ok);     border: 1px solid var(--ok-border); }
.eco-badge-cancelado  { background: rgba(190,18,60,0.08); color: var(--danger); border: 1px solid rgba(190,18,60,0.22); }

/* Empty state */
.eco-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 40px 24px;
    text-align: center;
}
.eco-empty-icon {
    width: 40px; height: 40px;
    border-radius: var(--r-sm);
    background: var(--surface-3);
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--muted-2);
    margin-bottom: 4px;
}
.eco-empty-title {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-2);
}
.eco-empty-desc {
    font-size: 0.75rem;
    color: var(--muted);
    max-width: 220px;
    line-height: 1.5;
}
.eco-empty-cta {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: 6px;
    padding: 7px 16px;
    background: var(--v);
    border-radius: var(--r-sm);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 600;
    text-decoration: none;
    transition: background 160ms var(--ease), box-shadow 160ms var(--ease), transform 110ms var(--ease);
}
@media (hover: hover) and (pointer: fine) {
    .eco-empty-cta:hover { background: #6d28d9; box-shadow: 0 4px 12px rgba(124,58,237,0.28); }
}
.eco-empty-cta:active { transform: scale(0.97); transition-duration: 90ms; }
.eco-empty-cta:focus-visible { outline: 2px solid var(--v); outline-offset: 2px; }

/* ── Right column ──────────────────────────────────────────── */
.eco-right-col {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* ── Catalog rows (same pattern as CMS) ────────────────────── */
.eco-row {
    display: flex;
    align-items: center;
    border-bottom: 1px solid var(--border);
}
.eco-row:last-child { border-bottom: none; }

.eco-row-link {
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
    .eco-row-link:hover { background: var(--surface-2); }
    .eco-row-link:hover .eco-row-icon {
        color: var(--v);
        border-color: var(--v-border);
        background: var(--v-soft);
    }
}
.eco-row-link:active    { background: var(--surface-3); transition-duration: 80ms; }
.eco-row-link:focus-visible { outline: 2px solid var(--v); outline-offset: -2px; border-radius: 2px; }

.eco-row-icon {
    width: 30px; height: 30px;
    border-radius: var(--r-sm);
    background: var(--surface-3);
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: var(--muted);
    transition: color 140ms var(--ease), border-color 140ms var(--ease), background 140ms var(--ease);
}
.eco-row-body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}
.eco-row-label {
    font-size: 0.82rem;
    font-weight: 500;
    color: var(--text-2);
}
.eco-row-desc {
    font-size: 0.68rem;
    color: var(--muted);
    margin-top: 1px;
}
.eco-row-count {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--muted);
    background: var(--surface-3);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 2px 9px;
    min-width: 32px;
    text-align: center;
    flex-shrink: 0;
}
.eco-row-count.has-items {
    color: var(--v-text);
    background: var(--v-soft);
    border-color: var(--v-border);
}

.eco-row-add {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px; height: 26px;
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
.eco-row-add::before { content: ''; position: absolute; inset: -9px; }
@media (hover: hover) and (pointer: fine) {
    .eco-row-add:hover { color: #fff; background: var(--v); border-color: var(--v); }
}
.eco-row-add:active { transform: scale(0.88); transition-duration: 90ms; }
.eco-row-add:focus-visible { outline: 2px solid var(--v); outline-offset: 3px; }

/* ── API banner ─────────────────────────────────────────────── */
.eco-api-banner {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 11px 14px;
    background: var(--v-soft);
    border: 1px solid var(--v-border);
    border-radius: var(--r-sm);
}
.eco-api-label {
    font-size: 0.62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--v-text);
    flex-shrink: 0;
    padding-top: 1px;
}
.eco-api-routes {
    display: flex;
    flex-direction: column;
    gap: 3px;
    min-width: 0;
}
.eco-api-route {
    font-size: 0.67rem;
    color: var(--muted);
    font-family: ui-monospace, 'SF Mono', monospace;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ── Reduced motion ────────────────────────────────────────── */
@media (prefers-reduced-motion: reduce) {
    .eco-dash,
    .eco-order-row { animation: none; opacity: 1; transform: none; }

    .eco-metric,
    .eco-order-row,
    .eco-row-link,
    .eco-row-add,
    .eco-empty-cta,
    .eco-panel-link { transition: none; }

    .eco-metric:active,
    .eco-row-add:active,
    .eco-empty-cta:active { transform: none; }
}
</style>

<div class="eco-dash">

    @php $tenant = filament()->getTenant(); @endphp

    {{-- Métricas clave --}}
    <div class="eco-metrics">

        <a href="{{ route('filament.ecommerce.resources.store-orders.index', ['tenant' => $tenant]) }}"
           class="eco-metric {{ $pendingOrders > 0 ? 'is-warn' : '' }}">
            <span class="eco-metric-value">{{ $pendingOrders }}</span>
            <span class="eco-metric-label">Órdenes pendientes</span>
            <span class="eco-metric-sub">
                {{ $pendingOrders > 0 ? 'Requieren atención' : 'Todo al día' }}
            </span>
        </a>

        <a href="{{ route('filament.ecommerce.resources.store-orders.index', ['tenant' => $tenant]) }}"
           class="eco-metric">
            <span class="eco-metric-value">${{ number_format($monthRevenue, 0) }}</span>
            <span class="eco-metric-label">Ingresos del mes</span>
            <span class="eco-metric-sub">Pagos aprobados</span>
        </a>

        <a href="{{ route('filament.ecommerce.resources.store-customers.index', ['tenant' => $tenant]) }}"
           class="eco-metric">
            <span class="eco-metric-value">{{ $customersCount }}</span>
            <span class="eco-metric-label">Clientes</span>
            <span class="eco-metric-sub">Registrados en el portal</span>
        </a>

        <a href="{{ route('filament.ecommerce.resources.store-products.index', ['tenant' => $tenant]) }}"
           class="eco-metric">
            <span class="eco-metric-value">{{ $productsPublished }}</span>
            <span class="eco-metric-label">Productos publicados</span>
            <span class="eco-metric-sub">
                {{ $productsTotal > $productsPublished ? ($productsTotal - $productsPublished) . ' sin publicar' : 'Todos visibles' }}
            </span>
        </a>

    </div>

    {{-- Grid principal --}}
    <div class="eco-main-grid">

        {{-- Columna izquierda: órdenes recientes --}}
        <div class="eco-panel">
            <div class="eco-panel-header">
                <span class="eco-panel-title">Órdenes recientes</span>
                @if ($ordersTotal > 0)
                    <a href="{{ route('filament.ecommerce.resources.store-orders.index', ['tenant' => $tenant]) }}"
                       class="eco-panel-link">
                        Ver todas ({{ $ordersTotal }}) →
                    </a>
                @endif
            </div>

            @if ($recentOrders->isEmpty())
                <div class="eco-empty">
                    <div class="eco-empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="1.5" stroke="currentColor" width="18" height="18" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                    </div>
                    <p class="eco-empty-title">Sin órdenes aún</p>
                    <p class="eco-empty-desc">Las órdenes aparecerán aquí cuando tus clientes realicen compras en el portal.</p>
                    @if ($productsPublished === 0)
                        <a href="{{ route('filament.ecommerce.resources.store-products.create', ['tenant' => $tenant]) }}"
                           class="eco-empty-cta">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="2" stroke="currentColor" width="13" height="13" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Agregar primer producto
                        </a>
                    @endif
                </div>
            @else
                @foreach ($recentOrders as $order)
                    <a href="{{ route('filament.ecommerce.resources.store-orders.edit', ['tenant' => $tenant, 'record' => $order->id]) }}"
                       class="eco-order-row"
                       style="--i: {{ $loop->index }}">
                        <span class="eco-order-num">{{ $order->numero }}</span>
                        <span class="eco-order-customer">
                            <span class="eco-order-name">
                                {{ $order->customer
                                    ? trim(($order->customer->nombre ?? '') . ' ' . ($order->customer->apellido ?? ''))
                                    : 'Sin cliente' }}
                            </span>
                            <span class="eco-order-time">{{ $order->created_at->diffForHumans() }}</span>
                        </span>
                        <span class="eco-order-badge eco-badge-{{ $order->estado }}">
                            {{ ucfirst($order->estado) }}
                        </span>
                        <span class="eco-order-amount">${{ number_format($order->total, 2) }}</span>
                    </a>
                @endforeach
            @endif
        </div>

        {{-- Columna derecha --}}
        <div class="eco-right-col">

            {{-- Catálogo --}}
            <div class="eco-panel">
                <div class="eco-panel-header">
                    <span class="eco-panel-title">Catálogo</span>
                </div>

                @php
                    $catalogRows = [
                        [
                            'label'  => 'Productos',
                            'desc'   => 'Precios, stock e imágenes',
                            'count'  => $productsTotal,
                            'icon'   => 'M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
                            'index'  => route('filament.ecommerce.resources.store-products.index',  ['tenant' => $tenant]),
                            'create' => route('filament.ecommerce.resources.store-products.create', ['tenant' => $tenant]),
                        ],
                        [
                            'label'  => 'Categorías',
                            'desc'   => 'Organización del catálogo',
                            'count'  => $categoriesCount,
                            'icon'   => 'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Zm-1.717 3.375a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z',
                            'index'  => route('filament.ecommerce.resources.store-categories.index',  ['tenant' => $tenant]),
                            'create' => route('filament.ecommerce.resources.store-categories.create', ['tenant' => $tenant]),
                        ],
                        [
                            'label'  => 'Cupones',
                            'desc'   => 'Descuentos y promociones',
                            'count'  => $couponsActive,
                            'icon'   => 'M16.5 6v.75a3 3 0 0 1-3 3h-6a3 3 0 0 1-3-3V6m16.5 0a2.25 2.25 0 0 0-2.25-2.25h-13.5A2.25 2.25 0 0 0 1.5 6m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6',
                            'index'  => route('filament.ecommerce.resources.store-coupons.index',  ['tenant' => $tenant]),
                            'create' => route('filament.ecommerce.resources.store-coupons.create', ['tenant' => $tenant]),
                        ],
                    ];
                @endphp

                @foreach ($catalogRows as $row)
                    <div class="eco-row">
                        <a href="{{ $row['index'] }}"
                           class="eco-row-link"
                           aria-label="Ver {{ $row['label'] }}">
                            <span class="eco-row-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="1.5" stroke="currentColor" width="15" height="15"
                                     aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $row['icon'] }}" />
                                </svg>
                            </span>
                            <span class="eco-row-body">
                                <span class="eco-row-label">{{ $row['label'] }}</span>
                                <span class="eco-row-desc">{{ $row['desc'] }}</span>
                            </span>
                            <span class="eco-row-count {{ $row['count'] > 0 ? 'has-items' : '' }}">
                                {{ $row['count'] }}
                            </span>
                        </a>
                        <a href="{{ $row['create'] }}"
                           class="eco-row-add"
                           title="Agregar {{ $row['label'] }}"
                           aria-label="Agregar {{ $row['label'] }}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="2" stroke="currentColor" width="12" height="12" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </a>
                    </div>
                @endforeach

            </div>

            {{-- API endpoints --}}
            <div class="eco-api-banner">
                <span class="eco-api-label">API</span>
                <span class="eco-api-routes">
                    <span class="eco-api-route">store/{{ $empresa->slug }}/products</span>
                    <span class="eco-api-route">store/{{ $empresa->slug }}/orders</span>
                </span>
            </div>

        </div>
    </div>

</div>
</x-filament-panels::page>
