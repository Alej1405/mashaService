<x-filament-panels::page>
@include('filament.contabilidad._estilos')
@php $money = fn ($n) => '$ ' . number_format((float) $n, 2, ',', '.'); @endphp

<div class="ct">
    @if ($total === 0 && $ventas->isEmpty())
        <section class="ct-panel">
            <header><h2 class="ct-tit">Bandeja vacía</h2></header>
            <p class="ct-vacio">
                No hay facturas del portal esperando registro. Todo lo que se importó está en la
                contabilidad con su asiento y su autorización. Para traer otro mes, usa
                «Subir archivo del SRI» arriba.
            </p>
        </section>
    @elseif ($total > 0)
        <div class="ct-aviso">
            <p>
                <strong>{{ $total }} facturas del portal sin registrar.</strong>
                Suman al formulario 104 pero no existen en la contabilidad: mientras queden así,
                el balance y la declaración no van a cuadrar y el mes no se puede cerrar.
            </p>
        </div>

        <section class="ct-panel">
            <header>
                <h2 class="ct-tit">Facturas por clasificar</h2>
                <span class="ct-pie2">
                    {{ $porProveedor->count() }} {{ $porProveedor->count() === 1 ? 'proveedor' : 'proveedores' }}
                    · el tipo sale del ítem del inventario; lo que decidas para uno se propone
                    para el resto de sus facturas
                </span>
            </header>

            @foreach ($comprobantes as $c)
                @php
                    $que = (string) ($eleccion[$c->id]['que'] ?? '');
                    $delProveedor = $porProveedor[$c->identificacion] ?? 1;
                @endphp
                <div class="ct-fila" style="align-items:flex-start; flex-wrap:wrap; gap:10px">
                    <div style="min-width:230px; flex:1">
                        <p class="ct-fila-t">{{ $c->razon_social ?: $c->identificacion }}</p>
                        <p class="ct-fila-s">
                            {{ $c->numero }} · {{ $c->fecha_emision->format('d/m/Y') }} ·
                            base {{ $money($c->base) }} · IVA {{ $money($c->iva) }} ·
                            <strong>{{ $money($c->total) }}</strong>
                            @if ($delProveedor > 1)
                                · <span style="color:var(--ac)">{{ $delProveedor }} facturas de este proveedor</span>
                            @endif
                        </p>
                    </div>

                    <div style="display:flex; gap:8px; align-items:center; flex:1 1 520px; flex-wrap:wrap">
                        {{-- Una sola pregunta: a qué entra. Si es un ítem del
                             inventario, su tipo ya dice si es materia prima,
                             insumo o activo fijo; no se pregunta dos veces. --}}
                        <select wire:model.live="eleccion.{{ $c->id }}.que" class="ct-select" style="flex:2 1 280px">
                            <option value="">— a qué entra —</option>
                            @foreach ($items as $tipo => $grupo)
                                <optgroup label="{{ ucfirst(str_replace('_', ' ', $tipo)) }}">
                                    @foreach ($grupo as $id => $nombre)
                                        <option value="item:{{ $id }}">{{ $nombre }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                            <optgroup label="Gasto">
                                @foreach ($tiposGasto as $id => $nombre)
                                    <option value="gasto:{{ $id }}">{{ $nombre }}</option>
                                @endforeach
                            </optgroup>
                        </select>

                        {{-- Cómo se pagó: decide la cuenta del asiento. El SRI no lo sabe. --}}
                        <select wire:model="eleccion.{{ $c->id }}.pago" class="ct-select" style="flex:1 1 200px"
                                aria-label="Cómo se pagó">
                            @foreach ($medios as $valor => $etiqueta)
                                <option value="{{ $valor }}">{{ $etiqueta }}</option>
                            @endforeach
                        </select>

                        @if (str_starts_with($que, 'item:'))
                            <input type="number" step="0.01" min="0.01"
                                   wire:model="eleccion.{{ $c->id }}.cantidad"
                                   class="ct-select" style="width:88px" placeholder="cant." aria-label="Cantidad">
                        @elseif (str_starts_with($que, 'gasto:'))
                            <label class="ct-fila-s" style="display:flex; align-items:center; gap:6px; white-space:nowrap">
                                <input type="checkbox" wire:model="eleccion.{{ $c->id }}.no_deducible">
                                no deducible
                            </label>
                        @endif

                        <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                                wire:click="clasificar({{ $c->id }})">
                            Registrar
                        </button>

                        @if ($delProveedor > 1)
                            <button type="button" class="ct-chip ct-chip-n" style="border:0;cursor:pointer"
                                    wire:click="clasificarProveedor('{{ $c->identificacion }}', {{ $c->id }})">
                                Las {{ $delProveedor }}
                            </button>
                        @endif
                    </div>

                </div>
            @endforeach
        </section>
    @endif

    @if ($ventas->isNotEmpty())
        <section class="ct-panel">
            <header>
                <h2 class="ct-tit">Ventas del portal sin registrar</h2>
                <span class="ct-pie2">facturas emitidas que no están en el ERP · se registran con su cliente,
                    su ingreso y la salida del producto del kardex</span>
            </header>

            @foreach ($ventas as $c)
                @php $que = (string) ($eleccion[$c->id]['que'] ?? ''); @endphp
                <div class="ct-fila" style="align-items:flex-start; flex-wrap:wrap; gap:10px">
                    <div style="min-width:230px; flex:1">
                        <p class="ct-fila-t">{{ $c->razon_social ?: $c->identificacion }}</p>
                        <p class="ct-fila-s">
                            {{ $c->numero }} · {{ $c->fecha_emision->format('d/m/Y') }} ·
                            <strong>{{ $money($c->total) }}</strong>
                            @if ($c->desglose !== 'xml') · la base se trae del SRI al registrar @endif
                        </p>
                    </div>

                    <div style="display:flex; gap:8px; align-items:center; flex:1 1 520px; flex-wrap:wrap">
                        <select wire:model.live="eleccion.{{ $c->id }}.que" class="ct-select" style="flex:2 1 240px"
                                aria-label="Qué se vendió">
                            <option value="">— qué se vendió —</option>
                            @foreach ($items as $tipo => $grupo)
                                <optgroup label="{{ ucfirst(str_replace('_', ' ', $tipo)) }}">
                                    @foreach ($grupo as $id => $nombre)
                                        <option value="item:{{ $id }}">{{ $nombre }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>

                        <input type="number" step="0.01" min="0.01" wire:model="eleccion.{{ $c->id }}.cantidad"
                               class="ct-select" style="width:88px" placeholder="cant." aria-label="Cantidad">

                        <select wire:model="eleccion.{{ $c->id }}.pago" class="ct-select" style="flex:1 1 200px"
                                aria-label="Cómo se cobró">
                            @foreach ($mediosVenta as $valor => $etiqueta)
                                <option value="{{ $valor }}">{{ $etiqueta }}</option>
                            @endforeach
                        </select>

                        <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                                @disabled(! str_starts_with($que, 'item:'))
                                wire:click="clasificar({{ $c->id }})">
                            Registrar venta
                        </button>
                    </div>
                </div>
            @endforeach
        </section>
    @endif

    @if ($meses->isNotEmpty())
        <section class="ct-panel">
            <header>
                <h2 class="ct-tit">ATS por mes</h2>
                <span class="ct-pie2">un mes sin facturas pendientes tiene su anexo completo: cada compra con su
                    autorización, su sustento y su forma de pago</span>
            </header>
            @foreach ($meses as $m)
                <div class="ct-fila">
                    <div>
                        <p class="ct-fila-t">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $m->mes)->locale('es')->translatedFormat('F Y') }}</p>
                        <p class="ct-fila-s">{{ $m->total }} comprobantes del portal
                            @if ($m->pendientes > 0) · {{ $m->pendientes }} sin registrar @endif</p>
                    </div>
                    @if ($m->pendientes > 0)
                        <span class="ct-chip ct-chip-wa">faltan {{ $m->pendientes }}</span>
                    @else
                        <a href="{{ $urlDeclaraciones }}" class="ct-btn-descarga">Pedir el ATS</a>
                    @endif
                </div>
            @endforeach
        </section>
    @endif

    <section class="ct-panel">
        <header><h2 class="ct-tit">Qué pasa al registrar cada una</h2></header>
        @foreach ([
            ['Materia prima, insumo o producto', 'entra al kardex con su costo, recalcula el promedio y genera el asiento', 'ok'],
            ['Activo fijo', 'se da de alta con su vida útil y se depreciará mes a mes', 'ok'],
            ['Gasto', 'asiento con el IVA como crédito tributario, no como gasto', 'ok'],
            ['Gasto no deducible', 'igual, pero marcado para la conciliación de la renta', 'wa'],
            ['Venta (factura emitida)', 'ingreso con su IVA, cobro o cuenta por cobrar, y descarga del costo del producto', 'ok'],
        ] as [$titulo, $detalle, $tono])
            <div class="ct-fila">
                <div><p class="ct-fila-t">{{ $titulo }}</p><p class="ct-fila-s">{{ $detalle }}</p></div>
                <span class="ct-chip ct-chip-{{ $tono }}">con asiento</span>
            </div>
        @endforeach
    </section>
</div>
</x-filament-panels::page>
