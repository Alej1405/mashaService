<x-filament-panels::page>
@include('filament.operaciones._estilos')
@php
    $money = fn ($n, $d = 4) => '$ ' . number_format((float) $n, $d, ',', '.');
    $r = $costo ?? [];
@endphp

{{-- Lo propio de esta pantalla: la rejilla receta | costo. Si alguna clase
     sirve para otra pantalla, se sube al include. --}}
<style>
    .fm { display:grid; grid-template-columns:1fr 340px; gap:16px; align-items:start; }
    .fm-linea { display:grid; grid-template-columns:1fr 92px 130px 78px 110px 36px; gap:8px;
                align-items:center; padding:10px 14px; border-bottom:1px solid var(--op-border-soft); }
    .fm-linea:last-child { border-bottom:0; }
    .fm-nombre { font-size:14px; font-weight:600; color:var(--op-text); }
    .fm-sub { font-size:11px; color:var(--op-muted); margin-top:2px; }
    .fm-num { text-align:right; font-variant-numeric:tabular-nums; font-size:13px; color:var(--op-text); }
    /* La flecha del desplegable la pinta Filament como imagen de fondo: sin
       no-repeat se dibuja una por cada ancho del campo. */
    .fm-campo { width:100%; box-sizing:border-box; padding:7px 9px; font-size:13px; font-family:inherit;
                border:1px solid var(--op-border); border-radius:9px; color:var(--op-text);
                background-color:var(--op-surface); background-repeat:no-repeat;
                background-position:right 8px center; background-size:1.1em 1.1em; }
    select.fm-campo { padding-right:28px; }
    .fm-quitar { border:0; background:none; cursor:pointer; color:var(--op-muted); font-size:17px; line-height:1; }
    .fm-quitar:hover { color:#be123c; }
    .fm-total { font-size:30px; font-weight:700; letter-spacing:-1px; color:var(--op-text); margin:4px 0 0; }
    .fm-aviso { padding:10px 14px; border-radius:10px; font-size:13px; margin-bottom:8px; }
    .fm-aviso-bloquea { background:#fff1f2; color:#be123c; }
    .fm-aviso-revisar { background:#fffbeb; color:var(--op-warn); }
    @media (max-width:1000px) {
        .fm { grid-template-columns:1fr; }
        .fm-linea { grid-template-columns:1fr 1fr; }
    }
</style>

<div class="op">
    @if ($presentaciones->isEmpty())
        <section class="op-panel">
            <header><h2 class="op-tit">Todavía no hay presentaciones</h2></header>
            <p style="padding:26px 18px; text-align:center; color:var(--op-muted); font-size:14px">
                Una receta se escribe sobre una presentación: la botella de 750 ml, el lote de 10 litros.
                Créala primero en el diseño del producto y vuelve aquí.
            </p>
        </section>
    @else
        <div class="op-barra">
            <select wire:model.live="presentacionId" class="fm-campo" style="flex:1 1 340px; padding:11px 14px; font-size:14px">
                @foreach ($presentaciones as $id => $nombre)
                    <option value="{{ $id }}">{{ $nombre }}</option>
                @endforeach
            </select>
            @if (($r['ok'] ?? false))
                <button type="button" class="op-btn" wire:click="guardarEstandar">Guardar como costo estándar</button>
            @endif
        </div>

        <div class="fm">
            {{-- La receta --}}
            <section class="op-panel">
                <header>
                    <h2 class="op-tit">Receta</h2>
                    <span class="fm-sub">
                        rinde {{ rtrim(rtrim(number_format((float) ($presentacion->cantidad_minima_produccion ?: 1), 2, ',', '.'), '0'), ',') }}
                        {{ $presentacion?->measurementUnit?->abreviatura ?? 'u' }}
                    </span>
                </header>

                @forelse ($lineas as $linea)
                    @php $calc = collect($r['lineas'] ?? [])->firstWhere('item_id', $linea->inventory_item_id); @endphp
                    <div class="fm-linea">
                        <div style="min-width:0">
                            <p class="fm-nombre">{{ $linea->inventoryItem?->nombre ?? 'ítem borrado' }}</p>
                            <p class="fm-sub">
                                {{ ucfirst(str_replace('_', ' ', $linea->inventoryItem?->type ?? '—')) }}
                                @if ($calc && ($calc['origen_costo'] ?? '') === 'receta') · se produce con su propia receta @endif
                            </p>
                        </div>
                        <div class="fm-num">{{ rtrim(rtrim(number_format((float) $linea->cantidad, 4, ',', '.'), '0'), ',') }}</div>
                        <div class="fm-sub">{{ $linea->measurementUnit?->abreviatura ?? '—' }}</div>
                        <div class="fm-num">{{ (float) $linea->merma_porcentaje > 0 ? rtrim(rtrim(number_format((float) $linea->merma_porcentaje, 2, ',', '.'), '0'), ',') . ' %' : '—' }}</div>
                        <div class="fm-num">
                            @if ($calc)
                                <strong>{{ $money($calc['costo'] ?? 0) }}</strong>
                                <div class="fm-sub">
                                    {{ rtrim(rtrim(number_format((float) ($calc['cantidad_normalizada'] ?? 0), 4, ',', '.'), '0'), ',') }}
                                    {{ $calc['unidad_normalizada'] ?? '' }}
                                </div>
                            @else
                                <span class="fm-sub">—</span>
                            @endif
                        </div>
                        <button type="button" class="fm-quitar" wire:click="quitarLinea({{ $linea->id }})" title="Quitar">&times;</button>
                    </div>
                @empty
                    <p style="padding:22px 18px; text-align:center; color:var(--op-muted); font-size:14px">
                        Sin líneas todavía. Añade el primer insumo abajo.
                    </p>
                @endforelse

                {{-- Añadir --}}
                <div class="fm-linea" style="background:var(--op-border-soft); border-top:1px solid var(--op-border)">
                    <select wire:model="nuevoItemId" class="fm-campo">
                        <option value="">— insumo o materia prima —</option>
                        @foreach ($items as $tipo => $grupo)
                            <optgroup label="{{ ucfirst(str_replace('_', ' ', $tipo)) }}">
                                @foreach ($grupo as $id => $nombre)
                                    <option value="{{ $id }}">{{ $nombre }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <input type="number" step="0.0001" min="0" wire:model="nuevaCantidad" class="fm-campo" placeholder="cant.">
                    <select wire:model="nuevaUnidadId" class="fm-campo">
                        <option value="">— unidad —</option>
                        @foreach ($unidades as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.01" min="0" max="99" wire:model="nuevaMerma" class="fm-campo" placeholder="merma %">
                    <button type="button" class="op-btn" wire:click="agregarLinea">Añadir</button>
                    <span></span>
                </div>
            </section>

            {{-- El costo --}}
            <section class="op-panel" style="padding:16px 18px">
                <h2 class="op-tit">Cuesta</h2>

                @if (! ($r['ok'] ?? false))
                    <div class="fm-aviso fm-aviso-bloquea" style="margin-top:10px">
                        {{ $r['error'] ?? 'Elige una presentación para calcular.' }}
                    </div>
                @else
                    <p class="fm-total">{{ $money($r['costo_unitario'] ?? 0) }}</p>
                    <p class="fm-sub">por unidad · lote de {{ $money($r['costo_lote'] ?? 0, 2) }}</p>

                    @if ($presentacion?->pvp_estimado > 0)
                        @php
                            $pvp = (float) $presentacion->pvp_estimado;
                            $margen = $pvp > 0 ? ($pvp - (float) ($r['costo_unitario'] ?? 0)) / $pvp * 100 : 0;
                        @endphp
                        <p class="fm-sub" style="margin-top:10px">
                            PVP estimado {{ $money($pvp, 2) }} · margen
                            <strong style="color:{{ $margen >= 30 ? 'var(--op-ok)' : 'var(--op-warn)' }}">
                                {{ number_format($margen, 1, ',', '.') }} %
                            </strong>
                        </p>
                    @endif

                    @if ($presentacion?->costo_estandar > 0)
                        <p class="fm-sub" style="margin-top:8px">
                            estándar guardado: {{ $money($presentacion->costo_estandar) }}
                            @if ($presentacion->costo_calculado_en) · {{ $presentacion->costo_calculado_en->diffForHumans() }} @endif
                        </p>
                    @endif

                    @foreach ($r['hallazgos'] ?? [] as $h)
                        <div class="fm-aviso fm-aviso-{{ $h['nivel'] }}" style="margin-top:10px">{{ $h['texto'] }}</div>
                    @endforeach
                @endif

                <p class="fm-sub" style="margin-top:14px; line-height:1.5">
                    El costo sale del promedio del kardex de cada insumo y se convierte a la unidad
                    de la receta. Lo que se produce —un macerado— trae el costo de su propia receta.
                </p>
            </section>
        </div>
    @endif
</div>
</x-filament-panels::page>
