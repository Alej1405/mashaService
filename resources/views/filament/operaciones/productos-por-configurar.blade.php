<x-filament-panels::page>
@include('filament.operaciones._estilos')

@php
    // Precio con los decimales que tiene, sin ceros de relleno: 0,28 · 6,087 · 50,00.
    $precio = fn ($v) => number_format((float) $v,
        round((float) $v, 2) == (float) $v ? 2 : (round((float) $v, 3) == (float) $v ? 3 : 4), ',', '.');

    // Primero los proveedores con compras detenidas, luego los que más productos tienen.
    $grupos = $productos->groupBy(fn ($p) => $p->supplier?->nombre ?? 'Sin proveedor')
        ->sortByDesc(fn ($g) => [$g->sum('esperando') > 0 ? 1 : 0, $g->count()]);

    $comprasDetenidas = $productos->sum('esperando');

    // Las opciones se arman una vez y se repiten en cada fila.
    $opciones = '<option value="">Elige qué es…</option>';
    foreach ($items as $tipo => $grupo) {
        $opciones .= '<optgroup label="' . e(ucfirst(str_replace('_', ' ', $tipo))) . '">';
        foreach ($grupo as $id => $nombre) {
            $opciones .= '<option value="item:' . $id . '">' . e($nombre) . '</option>';
        }
        $opciones .= '</optgroup>';
    }
    $opciones .= '<optgroup label="Gasto (no entra al inventario)">';
    foreach ($tiposGasto as $id => $nombre) {
        $opciones .= '<option value="gasto:' . $id . '">' . e($nombre) . '</option>';
    }
    $opciones .= '</optgroup>';
@endphp

<div class="op ppc" x-data="{ q: '' }">
    @if ($productos->isEmpty())
        <section class="op-panel">
            <p class="op-vacio">Todos los productos que facturan tus proveedores ya están configurados. Las compras entran solas al inventario.</p>
        </section>
    @else
        <header class="ppc-cabecera">
            <div>
                <h2 class="ppc-resumen">
                    {{ $productos->count() }} {{ $productos->count() === 1 ? 'producto' : 'productos' }}
                    de {{ $grupos->count() }} {{ $grupos->count() === 1 ? 'proveedor' : 'proveedores' }} sin configurar
                    @if ($comprasDetenidas > 0)
                        <span class="ppc-detenidas">· {{ $comprasDetenidas }} {{ $comprasDetenidas === 1 ? 'compra detenida' : 'compras detenidas' }}</span>
                    @endif
                </h2>
                <p class="ppc-ayuda">
                    Di qué es cada uno una sola vez. Si es mercadería o insumo, elige su ítem: la compra entra al kardex y recalcula el costo promedio.
                    Si el ítem no existe, créalo aquí mismo con «Crear ítem».
                </p>
            </div>
            <label class="ppc-buscar">
                <span class="sr-only">Buscar producto, proveedor o código</span>
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="9" cy="9" r="6"/><path d="m14 14 3.5 3.5" stroke-linecap="round"/></svg>
                <input type="search" x-model="q" placeholder="Buscar producto, proveedor o código">
            </label>
        </header>

        @foreach ($grupos as $proveedor => $lista)
            @php
                $esperan = $lista->sum('esperando');
                $textoGrupo = mb_strtolower($proveedor . ' ' . $lista->map(fn ($p) => $p->descripcion . ' ' . $p->codigo)->join(' '));
            @endphp
            <section class="op-panel ppc-grupo" data-texto="{{ $textoGrupo }}"
                     x-show="!q || $el.dataset.texto.includes(q.trim().toLowerCase())">
                <header class="ppc-grupo-cab">
                    <h3>{{ $proveedor }}</h3>
                    <span class="ppc-grupo-meta">
                        {{ $lista->count() }} {{ $lista->count() === 1 ? 'producto' : 'productos' }}
                        @if ($esperan > 0)
                            <span class="op-chip">{{ $esperan }} {{ $esperan === 1 ? 'compra espera' : 'compras esperan' }}</span>
                        @endif
                    </span>
                </header>

                @foreach ($lista as $p)
                    <div class="ppc-fila" wire:key="ppc-{{ $p->id }}"
                         data-texto="{{ mb_strtolower($proveedor . ' ' . $p->descripcion . ' ' . $p->codigo) }}"
                         x-show="!q || $el.dataset.texto.includes(q.trim().toLowerCase())">
                        <div class="ppc-producto">
                            <p class="ppc-nombre" title="{{ $p->descripcion }}">{{ $p->descripcion }}</p>
                            <p class="ppc-datos">
                                <span>Código <b>{{ $p->codigo }}</b></span>
                                @if ($p->ultimo_precio)
                                    <span>Último precio <b>$ {{ $precio($p->ultimo_precio) }}</b></span>
                                @endif
                                @if ($p->esperando > 0)
                                    <span class="ppc-espera">{{ $p->esperando }} {{ $p->esperando === 1 ? 'compra espera' : 'compras esperan' }}</span>
                                @endif
                            </p>
                        </div>

                        <select wire:model="eleccion.{{ $p->id }}" class="op-select ppc-select" aria-label="Qué es {{ $p->descripcion }}">
                            {!! $opciones !!}
                        </select>

                        <button type="button" class="op-btn ppc-crear" wire:click="mountAction('crearItem', { id: {{ $p->id }} })"
                                title="Crear un ítem nuevo para este producto">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M10 4.5v11M4.5 10h11" stroke-linecap="round"/></svg>
                            Crear ítem
                        </button>

                        <button type="button" class="op-btn op-btn-principal ppc-guardar"
                                wire:click="guardar({{ $p->id }})" wire:loading.attr="disabled" wire:target="guardar({{ $p->id }})">
                            <span wire:loading.remove wire:target="guardar({{ $p->id }})">Guardar</span>
                            <span wire:loading wire:target="guardar({{ $p->id }})">Guardando…</span>
                        </button>
                    </div>
                @endforeach
            </section>
        @endforeach

        <p class="op-vacio ppc-sin-resultados" x-cloak
           x-show="q && ! [...$root.querySelectorAll('.ppc-grupo')].some(g => g.dataset.texto.includes(q.trim().toLowerCase()))">
            Ningún producto, proveedor o código coincide con «<span x-text="q.trim()"></span>».
        </p>
    @endif
</div>
</x-filament-panels::page>
