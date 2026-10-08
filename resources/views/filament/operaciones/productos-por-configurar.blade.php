<x-filament-panels::page>
@include('filament.operaciones._estilos')

<div class="op">
    <section class="op-panel">
        <header style="padding:16px 18px; border-bottom:1px solid var(--op-border-soft)">
            <h2 class="op-tit">Productos facturados que el inventario no conoce</h2>
            <p class="op-hint">
                Di qué es cada uno una sola vez. Si es mercadería o insumo, elige su ítem: la compra entra al kardex y recalcula el costo promedio.
                Si no existe todavía, créalo en <a href="{{ $urlInventario }}" style="color:var(--op-accent); font-weight:700">Inventario</a> y vuelve.
            </p>
        </header>

        @forelse ($productos as $p)
            <div class="op-fila" style="flex-wrap:wrap; align-items:flex-start">
                <div style="flex:1 1 240px; min-width:0">
                    <p class="op-fila-titulo">{{ $p->descripcion }}</p>
                    <p class="op-fila-sub">
                        {{ $p->supplier?->nombre }} · código {{ $p->codigo }}
                        @if ($p->ultimo_precio) · último precio $ {{ number_format((float) $p->ultimo_precio, 4, ',', '.') }} @endif
                    </p>
                    @if ($p->esperando > 0)
                        <span class="op-chip" style="background:var(--op-warn-soft); color:var(--op-warn); margin-top:6px; display:inline-block">
                            {{ $p->esperando }} {{ $p->esperando === 1 ? 'compra espera' : 'compras esperan' }}
                        </span>
                    @endif
                </div>

                <div style="display:flex; gap:8px; flex:1 1 380px; flex-wrap:wrap">
                    <select wire:model="eleccion.{{ $p->id }}" class="op-select" style="flex:1 1 260px" aria-label="Qué es este producto">
                        <option value="">— qué es —</option>
                        @foreach ($items as $tipo => $grupo)
                            <optgroup label="{{ ucfirst(str_replace('_', ' ', $tipo)) }}">
                                @foreach ($grupo as $id => $nombre)
                                    <option value="item:{{ $id }}">{{ $nombre }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                        <optgroup label="Gasto (no entra al inventario)">
                            @foreach ($tiposGasto as $id => $nombre)
                                <option value="gasto:{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    <button type="button" class="op-btn op-btn-principal" wire:click="guardar({{ $p->id }})" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="guardar({{ $p->id }})">Guardar</span>
                        <span wire:loading wire:target="guardar({{ $p->id }})">Guardando…</span>
                    </button>
                </div>
            </div>
        @empty
            <p class="op-vacio">Todos los productos que facturan tus proveedores ya están configurados. Las compras entran solas al inventario.</p>
        @endforelse
    </section>
</div>
</x-filament-panels::page>
