<x-filament-panels::page>
@include('filament.operaciones._estilos')
@php
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 4, ',', '.'), '0'), ',');
@endphp

<div class="op">
    @if ($enCorrida->isEmpty() && $agendados->isEmpty())
        <section class="op-panel">
            <header><h2 class="op-tit">No hay pedidos pendientes de producir</h2></header>
            <p style="padding:26px 18px; text-align:center; color:var(--op-muted); font-size:14px">
                Cuando entren pedidos por producir, aquí se arma la corrida: se juntan por
                categoría y se calcula qué materias primas procesadas preparar antes de ensamblar.
            </p>
        </section>
    @else
        {{-- Preparación previa: lo primero, porque es lo que se hace antes de ensamblar --}}
        @if (!empty($semielaborados))
            <section class="op-panel" style="border-left:4px solid #d97706">
                <header><h2 class="op-tit">Preparación previa — antes de ensamblar</h2></header>
                @foreach ($semielaborados as $s)
                    <div style="padding:12px 16px; border-bottom:1px solid var(--op-border-soft)">
                        <div style="display:flex; justify-content:space-between; align-items:baseline; gap:12px; flex-wrap:wrap">
                            <div>
                                <strong style="font-size:15px; color:var(--op-text)">{{ $s['nombre'] }}</strong>
                                @if ($s['bajo_pedido'])
                                    <span style="font-size:11px; background:#ecfeff; color:#0e7490; padding:2px 8px; border-radius:999px; margin-left:6px">fresco por pedido</span>
                                @endif
                            </div>
                            <div style="text-align:right">
                                @if ($s['a_preparar'] > 0)
                                    <span style="font-size:18px; font-weight:700; color:#b45309">
                                        Prepara {{ $fmt($s['a_preparar']) }} {{ $s['unidad'] }}
                                    </span>
                                @else
                                    <span style="font-size:14px; font-weight:600; color:var(--op-ok)">✓ Hay stock suficiente</span>
                                @endif
                            </div>
                        </div>
                        <div style="font-size:12px; color:var(--op-muted); margin-top:4px">
                            Pide el pedido: {{ $fmt($s['demanda']) }} {{ $s['unidad'] }} · En stock: {{ $fmt($s['stock']) }} {{ $s['unidad'] }}
                            @if (!empty($s['consume']) && $s['a_preparar'] > 0)
                                <br>Consume: {{ collect($s['consume'])->map(fn ($c) => $fmt($c['cantidad']) . ' ' . $c['unidad'] . ' de ' . $c['nombre'])->join(' · ') }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </section>
        @endif

        {{-- Producción consolidada por categoría --}}
        <section class="op-panel">
            <header><h2 class="op-tit">Producción consolidada</h2></header>
            @forelse ($porCategoria as $categoria => $productos)
                <div style="padding:10px 16px; border-bottom:1px solid var(--op-border-soft)">
                    <p style="font-weight:700; color:var(--op-text); margin-bottom:6px">{{ $categoria }}</p>
                    @foreach ($productos as $nombre => $cantidad)
                        <div style="display:flex; justify-content:space-between; font-size:13px; padding:3px 0">
                            <span style="color:var(--op-text)">{{ $nombre }}</span>
                            <span style="font-variant-numeric:tabular-nums; color:var(--op-muted)">{{ $fmt($cantidad) }}</span>
                        </div>
                    @endforeach
                </div>
            @empty
                <p style="padding:20px 16px; text-align:center; color:var(--op-muted); font-size:14px">
                    Los pedidos de la corrida no tienen productos con diseño para producir.
                </p>
            @endforelse
        </section>

        {{-- Pedidos en la corrida --}}
        @if ($enCorrida->isNotEmpty())
            <section class="op-panel">
                <header><h2 class="op-tit">Pedidos en esta corrida ({{ $enCorrida->count() }})</h2></header>
                @foreach ($enCorrida as $pedido)
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; padding:10px 16px; border-bottom:1px solid var(--op-border-soft)">
                        <div style="min-width:0">
                            <strong style="color:var(--op-text)">{{ $pedido->numero }}</strong>
                            <span style="color:var(--op-muted); font-size:13px"> · {{ $pedido->customer?->nombre_completo ?? 'Cliente' }} · {{ $pedido->orderItems->count() }} prod.</span>
                        </div>
                        <button type="button" class="op-btn"
                                wire:click="mountAction('posponer', {{ \Illuminate\Support\Js::from(['pedido_id' => $pedido->id]) }})">
                            Procesar luego
                        </button>
                    </div>
                @endforeach
            </section>
        @endif

        {{-- Agendados para después --}}
        @if ($agendados->isNotEmpty())
            <section class="op-panel">
                <header><h2 class="op-tit">Agendados para después</h2></header>
                @foreach ($agendados as $pedido)
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; padding:10px 16px; border-bottom:1px solid var(--op-border-soft)">
                        <div style="min-width:0">
                            <strong style="color:var(--op-text)">{{ $pedido->numero }}</strong>
                            <span style="color:var(--op-muted); font-size:13px"> · {{ $pedido->customer?->nombre_completo ?? 'Cliente' }} · para el {{ $pedido->fecha_programada->format('d/m/Y') }}</span>
                        </div>
                        <button type="button" class="op-btn" style="background:transparent; color:var(--op-muted); border:1px solid var(--op-border)"
                                wire:click="mountAction('retomar', {{ \Illuminate\Support\Js::from(['pedido_id' => $pedido->id]) }})">
                            Traer a hoy
                        </button>
                    </div>
                @endforeach
            </section>
        @endif
    @endif

    <x-filament-actions::modals />
</div>
</x-filament-panels::page>
