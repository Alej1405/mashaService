<x-filament-panels::page>
@include('filament.contabilidad._estilos')
@php $money = fn ($n) => '$ ' . number_format((float) $n, 2, ',', '.'); @endphp

<div class="ct">
    @if (count($anios) > 1)
        {{-- Una bandeja de un año entero se trabaja año por año. --}}
        <div style="display:flex; gap:8px; flex-wrap:wrap" role="tablist" aria-label="Año">
            @foreach ([null, ...$anios] as $a)
                <button type="button" role="tab" aria-selected="{{ $anio === $a ? 'true' : 'false' }}"
                        wire:click="$set('anio', {{ $a ?? 'null' }})"
                        class="ct-chip {{ $anio === $a ? 'ct-chip-ok' : 'ct-chip-n' }}"
                        style="border:0; cursor:pointer; padding:8px 14px; font-size:.9rem">
                    {{ $a ?? 'Todos los años' }}
                </button>
            @endforeach
        </div>
    @endif
    @if ($total === 0 && $ventas->isEmpty() && $productosNuevos->isEmpty())
        <section class="ct-panel">
            <header><h2 class="ct-tit">Bandeja vacía</h2></header>
            <p class="ct-vacio">
                No hay facturas del portal esperando registro. Todo lo que se importó está en la
                contabilidad con su asiento y su autorización. Para traer otro mes, usa
                «Subir archivo del SRI» arriba.
            </p>
        </section>
    @endif

    @if ($avance['corriendo'] ?? false)
        {{-- Se refresca solo mientras corre el registro en segundo plano. --}}
        <div class="ct-aviso" wire:poll.5s>
            <p><strong>Registrando en segundo plano: {{ $avance['hechos'] ?? 0 }} de {{ $avance['total'] ?? '…' }}.</strong>
                Cada factura se consulta en el SRI. Te aviso en la campana al terminar.</p>
        </div>
    @elseif (! empty($avance['falla']))
        <div class="ct-aviso"><p><strong>El registro se detuvo:</strong> {{ $avance['falla'] }}. Lo registrado quedó; vuelve a tocar «Registrar todas».</p></div>
    @endif

    @if ($total > 0)
        <div class="ct-aviso" style="display:flex; align-items:center; gap:12px; flex-wrap:wrap">
            <p style="flex:1 1 320px">
                <strong>{{ $total }} facturas del portal sin registrar{{ $anio ? " en {$anio}" : '' }}</strong>
                @if ($total > 120) (se muestran las primeras 120) @endif.
                Cada una se registra con el detalle que trae del SRI: sus productos, cantidades, precios e IVA.
                Solo elige cómo se pagó.
            </p>
            @unless ($avance['corriendo'] ?? false)
                <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                        wire:click="registrarTodas" wire:loading.attr="disabled" wire:target="registrarTodas">
                    Registrar todas{{ $anio ? " las de {$anio}" : '' }} con su detalle
                </button>
            @endunless
        </div>

        <section class="ct-panel">
            <header>
                <h2 class="ct-tit">Compras por registrar</h2>
                <span class="ct-pie2">
                    {{ $porProveedor->count() }} {{ $porProveedor->count() === 1 ? 'proveedor' : 'proveedores' }}
                    · el pago que elijas para uno se propone para el resto de sus facturas
                </span>
            </header>

            @foreach ($comprobantes as $c)
                @php
                    $que = (string) ($eleccion[$c->id]['que'] ?? '');
                    $manual = in_array($c->desglose, $sinDetalle, true);
                    $delProveedor = $porProveedor[$c->identificacion] ?? 1;
                @endphp
                <div class="ct-fila" style="align-items:flex-start; flex-wrap:wrap; gap:10px">
                    <div style="min-width:230px; flex:1">
                        <p class="ct-fila-t">{{ $c->razon_social ?: $c->identificacion }}</p>
                        <p class="ct-fila-s">
                            {{ $c->numero }} · {{ $c->fecha_emision->format('d/m/Y') }} ·
                            base {{ $money($c->base) }} · IVA {{ $money($c->iva) }} ·
                            <strong>{{ $money($c->total) }}</strong>
                        </p>
                        @if ($manual)
                            <p class="ct-fila-s" style="color:var(--wa)">El SRI ya no devuelve esta factura: di qué fue.</p>
                        @endif
                    </div>

                    <div style="display:flex; gap:8px; align-items:center; flex:1 1 460px; flex-wrap:wrap">
                        @if ($manual)
                            <select wire:model.live="eleccion.{{ $c->id }}.que" class="ct-select" style="flex:2 1 240px"
                                    aria-label="Qué fue">
                                <option value="">— qué fue —</option>
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
                        @endif

                        {{-- Cómo se pagó: decide la cuenta del asiento. El SRI no lo sabe. --}}
                        <select wire:model="eleccion.{{ $c->id }}.pago" class="ct-select" style="flex:1 1 200px"
                                aria-label="Cómo se pagó">
                            @foreach ($medios as $valor => $etiqueta)
                                <option value="{{ $valor }}">{{ $etiqueta }}</option>
                            @endforeach
                        </select>

                        @if ($manual && str_starts_with($que, 'item:'))
                            <input type="number" step="0.01" min="0.01" wire:model="eleccion.{{ $c->id }}.cantidad"
                                   class="ct-select" style="width:88px" placeholder="cant." aria-label="Cantidad">
                        @elseif ($manual && str_starts_with($que, 'gasto:'))
                            <label class="ct-fila-s" style="display:flex; align-items:center; gap:6px; white-space:nowrap">
                                <input type="checkbox" wire:model="eleccion.{{ $c->id }}.no_deducible"> no deducible
                            </label>
                        @endif

                        @if ($manual)
                            <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                                    wire:click="clasificar({{ $c->id }})">Registrar</button>
                            @if ($delProveedor > 1)
                                <button type="button" class="ct-chip ct-chip-n" style="border:0;cursor:pointer"
                                        wire:click="clasificarProveedor('{{ $c->identificacion }}', {{ $c->id }})">
                                    Las {{ $delProveedor }}
                                </button>
                            @endif
                        @else
                            <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                                    wire:click="registrar({{ $c->id }})" wire:loading.attr="disabled"
                                    wire:target="registrar({{ $c->id }})">Registrar con su detalle</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </section>
    @endif

    @if ($productosNuevos->isNotEmpty())
        <section class="ct-panel">
            <header>
                <h2 class="ct-tit">Productos nuevos de proveedores</h2>
                <span class="ct-pie2">aparecieron en una factura y aún no se sabe qué son · se dice una vez
                    y las compras que los esperan se registran solas, igual que las próximas</span>
            </header>
            @foreach ($productosNuevos as $p)
                <div class="ct-fila" style="align-items:flex-start; flex-wrap:wrap; gap:10px">
                    <div style="min-width:230px; flex:1">
                        <p class="ct-fila-t">{{ $p->descripcion }}</p>
                        <p class="ct-fila-s">{{ $p->supplier?->nombre }} · código {{ $p->codigo }}
                            @if ($p->ultimo_precio) · último precio {{ $money($p->ultimo_precio) }} @endif</p>
                    </div>
                    <div style="display:flex; gap:8px; align-items:center; flex:1 1 420px; flex-wrap:wrap">
                        <select wire:model="productos.{{ $p->id }}" class="ct-select" style="flex:2 1 260px"
                                aria-label="Qué es este producto">
                            <option value="">— qué es —</option>
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
                        <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                                wire:click="configurarProducto({{ $p->id }})">Guardar</button>
                    </div>
                </div>
            @endforeach
        </section>
    @endif

    @if ($ventas->isNotEmpty())
        <section class="ct-panel">
            <header>
                <h2 class="ct-tit">Ventas del portal sin registrar</h2>
                <span class="ct-pie2">facturas emitidas que no están en el ERP · cada producto se reconoce en el
                    inventario por su código o su nombre, con su cliente, su ingreso y la salida del kardex</span>
            </header>

            @foreach ($ventas as $c)
                @php
                    $que = (string) ($eleccion[$c->id]['que'] ?? '');
                    $manual = in_array($c->desglose, $sinDetalle, true);
                @endphp
                <div class="ct-fila" style="align-items:flex-start; flex-wrap:wrap; gap:10px">
                    <div style="min-width:230px; flex:1">
                        <p class="ct-fila-t">{{ $c->razon_social ?: $c->identificacion }}</p>
                        <p class="ct-fila-s">{{ $c->numero }} · {{ $c->fecha_emision->format('d/m/Y') }} ·
                            <strong>{{ $money($c->total) }}</strong></p>
                        @if ($manual)
                            <p class="ct-fila-s" style="color:var(--wa)">No se pudo leer su detalle: di qué se vendió.</p>
                        @endif
                    </div>

                    <div style="display:flex; gap:8px; align-items:center; flex:1 1 460px; flex-wrap:wrap">
                        @if ($manual)
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
                        @endif

                        <select wire:model="eleccion.{{ $c->id }}.pago" class="ct-select" style="flex:1 1 200px"
                                aria-label="Cómo se cobró">
                            @foreach ($mediosVenta as $valor => $etiqueta)
                                <option value="{{ $valor }}">{{ $etiqueta }}</option>
                            @endforeach
                        </select>

                        @if ($manual)
                            <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                                    @disabled(! str_starts_with($que, 'item:'))
                                    wire:click="clasificar({{ $c->id }})">Registrar venta</button>
                        @else
                            <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                                    wire:click="registrar({{ $c->id }})" wire:loading.attr="disabled"
                                    wire:target="registrar({{ $c->id }})">Registrar con su detalle</button>
                        @endif
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
</div>
</x-filament-panels::page>
