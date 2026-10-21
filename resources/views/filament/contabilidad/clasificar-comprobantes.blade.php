<x-filament-panels::page>
@include('filament.contabilidad._estilos')
@php
    $money = fn ($n) => '$ ' . number_format((float) $n, 2, ',', '.');
    $cant = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
    $tarjetas = [
        'por_registrar' => ['Por registrar', 'el detalle se trae del SRI al registrar', 'n'],
        'sin_detalle'   => ['Sin detalle', 'el SRI ya no las entrega: sube su XML o dilas a mano', 'wa'],
        'esperan'       => ['Esperan un producto', 'tienen su detalle; falta decir qué es un producto nuevo', 'wa'],
        'registradas'   => ['Registradas', 'con su asiento; despliega cada una para ver su detalle', 'ok'],
    ];
@endphp

<div class="ct">
    {{-- Año y estado: lo primero que se elige. --}}
    @if (count($anios) > 1)
        <div class="ct-barra" role="tablist" aria-label="Año">
            @foreach ([null, ...$anios] as $a)
                <button type="button" role="tab" aria-selected="{{ $anio === $a ? 'true' : 'false' }}"
                        class="ct-pestana {{ $anio === $a ? 'es-activa' : '' }}"
                        wire:click="$set('anio', {{ $a ?? 'null' }})">
                    {{ $a ?? 'Todos los años' }}
                </button>
            @endforeach
        </div>
    @endif

    <div class="ct-rej-4">
        @foreach ($tarjetas as $clave => [$titulo, $pie, $tono])
            <button type="button" class="ct-card ct-card-boton" aria-pressed="{{ $vista === $clave ? 'true' : 'false' }}"
                    wire:click="verVista('{{ $clave }}')">
                <p class="ct-tit">{{ $titulo }}</p>
                <p class="ct-val" style="{{ $conteos[$clave] > 0 && $tono !== 'n' ? 'color:var(--' . $tono . ')' : '' }}">{{ $conteos[$clave] }}</p>
                <p class="ct-pie">{{ $pie }}</p>
            </button>
        @endforeach
    </div>

    @if ($avance['corriendo'] ?? false)
        {{-- Se refresca solo mientras corre el registro en segundo plano. --}}
        <div class="ct-aviso" wire:poll.5s>
            <p><strong>Registrando en segundo plano: {{ $avance['hechos'] ?? 0 }} de {{ $avance['total'] ?? '…' }}.</strong>
                Cada factura se consulta en el SRI. Te aviso en la campana al terminar.</p>
        </div>
    @elseif (! empty($avance['falla']))
        <div class="ct-aviso"><p><strong>El registro se detuvo:</strong> {{ $avance['falla'] }}. Lo registrado quedó; vuelve a tocar «Registrar todas».</p></div>
    @endif

    {{-- ── Por registrar y sin detalle: las que faltan ─────────────────── --}}
    @if (in_array($vista, ['por_registrar', 'sin_detalle'], true))
        @if ($comprobantes->isEmpty() && $ventas->isEmpty())
            <section class="ct-panel">
                <p class="ct-fila-s" style="padding:18px">
                    @if ($vista === 'por_registrar')
                        No hay facturas por registrar{{ $anio ? " en {$anio}" : '' }}. Para traer otro mes, usa «Subir archivo del SRI» arriba.
                    @else
                        No hay facturas sin detalle{{ $anio ? " en {$anio}" : '' }}: todo lo que el SRI entregó se registró con su detalle.
                    @endif
                </p>
            </section>
        @endif

        @if ($comprobantes->isNotEmpty())
            <section class="ct-panel">
                <header>
                    <div>
                        <h2 class="ct-tit">{{ $vista === 'por_registrar' ? 'Compras por registrar' : 'Compras sin detalle' }}</h2>
                        <p class="ct-pie2">
                            @if ($vista === 'por_registrar')
                                Elige cómo se pagó cada una: lo que elijas se propone para el resto de ese proveedor
                            @else
                                Lo mejor es subir su XML arriba. Si no lo tienes, di qué fue
                            @endif
                            @if ($conteos[$vista] > 120) · se muestran las primeras 120 @endif
                        </p>
                    </div>
                    @if ($vista === 'por_registrar' && ! ($avance['corriendo'] ?? false))
                        <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                                wire:click="registrarTodas" wire:loading.attr="disabled" wire:target="registrarTodas">
                            Registrar todas{{ $anio ? " las de {$anio}" : '' }}
                        </button>
                    @endif
                </header>

                @foreach ($comprobantes as $c)
                    @php
                        $que = (string) ($eleccion[$c->id]['que'] ?? '');
                        $manual = $vista === 'sin_detalle';
                        $delProveedor = $porProveedor[$c->identificacion] ?? 1;
                    @endphp
                    <div class="ct-fila" style="align-items:flex-start; flex-wrap:wrap; gap:10px" wire:key="c-{{ $c->id }}">
                        <div style="min-width:230px; flex:1">
                            <p class="ct-fila-t">{{ $c->razon_social ?: $c->identificacion }}</p>
                            <p class="ct-fila-s">
                                {{ $c->numero }} · {{ $c->fecha_emision->format('d/m/Y') }} ·
                                base {{ $money($c->base) }} · IVA {{ $money($c->iva) }} ·
                                <strong>{{ $money($c->total) }}</strong>
                            </p>
                            <p style="margin:6px 0 0">
                                @if ($manual)
                                    <span class="ct-chip ct-chip-wa">Sin detalle · sube su XML</span>
                                @else
                                    <span class="ct-chip ct-chip-n">Detalle: se trae del SRI</span>
                                @endif
                            </p>
                        </div>

                        <div style="display:flex; gap:8px; align-items:center; flex:1 1 460px; flex-wrap:wrap">
                            @if ($manual)
                                <select wire:model.live="eleccion.{{ $c->id }}.que" class="ct-select" style="flex:2 1 240px" aria-label="Qué fue">
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
                            <select wire:model="eleccion.{{ $c->id }}.pago" class="ct-select" style="flex:1 1 200px" aria-label="Cómo se pagó">
                                <option value="">Como diga su XML (se audita abajo)</option>
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
                                        @disabled($que === '') wire:click="clasificar({{ $c->id }})">Registrar a mano</button>
                                @if ($delProveedor > 1)
                                    <button type="button" class="ct-pestana" @disabled($que === '')
                                            wire:click="clasificarProveedor('{{ $c->identificacion }}', {{ $c->id }})">Las {{ $delProveedor }} de este proveedor</button>
                                @endif
                            @else
                                <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                                        wire:click="registrar({{ $c->id }})" wire:loading.attr="disabled"
                                        wire:target="registrar({{ $c->id }})">
                                    <span wire:loading.remove wire:target="registrar({{ $c->id }})">Registrar</span>
                                    <span wire:loading wire:target="registrar({{ $c->id }})">Consultando al SRI…</span>
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
                    <div>
                        <h2 class="ct-tit">Ventas {{ $vista === 'por_registrar' ? 'por registrar' : 'sin detalle' }}</h2>
                        <p class="ct-pie2">Facturas emitidas: cada producto se reconoce en el inventario por su código o su nombre</p>
                    </div>
                </header>

                @foreach ($ventas as $c)
                    @php
                        $que = (string) ($eleccion[$c->id]['que'] ?? '');
                        $manual = $vista === 'sin_detalle';
                    @endphp
                    <div class="ct-fila" style="align-items:flex-start; flex-wrap:wrap; gap:10px" wire:key="v-{{ $c->id }}">
                        <div style="min-width:230px; flex:1">
                            <p class="ct-fila-t">{{ $c->razon_social ?: $c->identificacion }}</p>
                            <p class="ct-fila-s">{{ $c->numero }} · {{ $c->fecha_emision->format('d/m/Y') }} ·
                                <strong>{{ $money($c->total) }}</strong></p>
                            <p style="margin:6px 0 0">
                                <span class="ct-chip {{ $manual ? 'ct-chip-wa' : 'ct-chip-n' }}">
                                    {{ $manual ? 'Sin detalle · di qué se vendió' : 'Detalle: se trae del SRI' }}
                                </span>
                            </p>
                        </div>

                        <div style="display:flex; gap:8px; align-items:center; flex:1 1 460px; flex-wrap:wrap">
                            @if ($manual)
                                <select wire:model.live="eleccion.{{ $c->id }}.que" class="ct-select" style="flex:2 1 240px" aria-label="Qué se vendió">
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

                            <select wire:model="eleccion.{{ $c->id }}.pago" class="ct-select" style="flex:1 1 200px" aria-label="Cómo se cobró">
                                @foreach ($mediosVenta as $valor => $etiqueta)
                                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                @endforeach
                            </select>

                            @if ($manual)
                                <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                                        @disabled(! str_starts_with($que, 'item:')) wire:click="clasificar({{ $c->id }})">Registrar a mano</button>
                            @else
                                <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                                        wire:click="registrar({{ $c->id }})" wire:loading.attr="disabled" wire:target="registrar({{ $c->id }})">
                                    <span wire:loading.remove wire:target="registrar({{ $c->id }})">Registrar</span>
                                    <span wire:loading wire:target="registrar({{ $c->id }})">Consultando al SRI…</span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </section>
        @endif
    @endif

    {{-- ── Sin detalle y esperan: subir el XML y completar cada factura aquí mismo ── --}}
    @if (in_array($vista, ['sin_detalle', 'esperan'], true))
        @livewire(\App\Livewire\CompletarFacturas::class)
    @endif

    {{-- ── Registradas: con lo que se registró ───────────────────────────── --}}
    @if ($vista === 'registradas')
        <section class="ct-panel">
            <header>
                <div>
                    <h2 class="ct-tit">Facturas registradas</h2>
                    <p class="ct-pie2">
                        Las registradas a mano se completan subiendo su XML (pestaña «Sin detalle»): toman el detalle real y se rehace su asiento
                        @if ($conteos[$vista] > 120) · se muestran las 120 más recientes @endif
                    </p>
                </div>
            </header>

            @forelse ($registradas as $c)
                @php $d = $detalle[$c->id]; @endphp
                <div class="ct-fila" style="flex-wrap:wrap" wire:key="r-{{ $c->id }}">
                    <div style="min-width:230px; flex:1">
                        <p class="ct-fila-t">{{ $c->razon_social ?: $c->identificacion }}</p>
                        <p class="ct-fila-s">{{ $d['tipo'] }} · {{ $c->numero }} · {{ $c->fecha_emision->format('d/m/Y') }} ·
                            <strong>{{ $money($c->total) }}</strong></p>
                    </div>
                    @if ($d['con_detalle'])
                        <span class="ct-chip ct-chip-ok">Con detalle · {{ count($d['lineas']) }} {{ count($d['lineas']) === 1 ? 'producto' : 'productos' }}</span>
                    @else
                        <span class="ct-chip ct-chip-n">A mano · sin detalle</span>
                    @endif

                    @if ($d['lineas'])
                        <details class="ct-detalle">
                            <summary>{{ $d['con_detalle'] ? 'Ver el detalle' : 'Ver lo registrado' }}</summary>
                            <ul class="ct-lineas">
                                @foreach ($d['lineas'] as [$texto, $cantidad, $valor])
                                    <li><span>{{ $texto }}@if ($cantidad) · {{ $cant($cantidad) }}@endif</span><span>{{ $money($valor) }}</span></li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </div>
            @empty
                <p class="ct-fila-s" style="padding:18px">
                    Todavía no hay facturas registradas{{ $anio ? " en {$anio}" : '' }}.
                </p>
            @endforelse
        </section>
    @endif

    {{-- ── El ATS de cada mes: el resultado de vaciar la bandeja ───────── --}}
    @if ($meses->isNotEmpty())
        <section class="ct-panel">
            <header>
                <div>
                    <h2 class="ct-tit">ATS por mes</h2>
                    <p class="ct-pie2">Un mes sin facturas pendientes tiene su anexo completo: cada compra con su autorización, su sustento y su forma de pago</p>
                </div>
            </header>
            @foreach ($meses as $m)
                <div class="ct-fila">
                    <div>
                        <p class="ct-fila-t">{{ ucfirst(\Illuminate\Support\Carbon::createFromFormat('Y-m', $m->mes)->locale('es')->translatedFormat('F Y')) }}</p>
                        <p class="ct-fila-s">{{ $m->total }} comprobantes del portal
                            @if ($m->pendientes > 0) · {{ $m->pendientes }} sin registrar @endif</p>
                    </div>
                    @if ($m->pendientes > 0)
                        <span class="ct-chip ct-chip-wa">Faltan {{ $m->pendientes }}</span>
                    @else
                        <a href="{{ $urlDeclaraciones }}" class="ct-btn-descarga">Pedir el ATS</a>
                    @endif
                </div>
            @endforeach
        </section>
    @endif
</div>
</x-filament-panels::page>
