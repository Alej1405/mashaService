<x-filament-panels::page>
@include('filament.contabilidad._estilos')
@php
    $money = fn ($n) => '$ ' . number_format((float) $n, 2, ',', '.');
    $cant = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
    $tarjetas = [
        'por_registrar' => ['Por traer del SRI', 'todavía no se trae su detalle', 'n'],
        'esperan'       => ['Listas para registrar', 'con su detalle: elige cómo se pagaron y regístralas', 'wa'],
        'sin_detalle'   => ['Falta el detalle', 'el SRI ya no las entrega: sube su XML', 'wa'],
        'registradas'   => ['Registradas', 'con su asiento; despliega cada una para ver su detalle', 'ok'],
    ];
    $nombresMes = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    // "en marzo de 2026", "en 2026" o nada: lo que se está mirando, para los mensajes vacíos.
    $periodo = $anio ? ' en ' . ($mes ? mb_strtolower($nombresMes[$mes]) . ' de ' : '') . $anio : '';
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

    {{-- Mes: solo dentro de un año elegido. --}}
    @if ($anio && count($mesesDelAnio) > 1)
        <div class="ct-barra" role="tablist" aria-label="Mes">
            @foreach ([null, ...$mesesDelAnio] as $m)
                <button type="button" role="tab" aria-selected="{{ $mes === $m ? 'true' : 'false' }}"
                        class="ct-pestana {{ $mes === $m ? 'es-activa' : '' }}"
                        wire:click="$set('mes', {{ $m ?? 'null' }})">
                    {{ $m ? $nombresMes[$m] : "Todo {$anio}" }}
                </button>
            @endforeach
        </div>
    @endif

    {{-- Proveedor: se filtran sus facturas y se registran todas de una con un solo pago. --}}
    @if ($proveedores->count() > 1 || $proveedor)
        <div class="ct-barra">
            <select wire:model.live="proveedor" class="ct-select" style="flex:1 1 280px; max-width:420px" aria-label="Proveedor">
                <option value="">Todos los proveedores</option>
                @foreach ($proveedores as $p)
                    <option value="{{ $p->identificacion }}">{{ $p->nombre }} · {{ $p->n }}</option>
                @endforeach
                @if ($proveedor && ! $proveedores->contains('identificacion', $proveedor))
                    <option value="{{ $proveedor }}">{{ $proveedor }} · sin pendientes</option>
                @endif
            </select>
            @if ($proveedor)
                <button type="button" class="ct-pestana" wire:click="$set('proveedor', null)">Quitar filtro</button>
            @endif
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
            <p><strong>Trayendo el detalle del SRI: {{ $avance['hechos'] ?? 0 }} de {{ $avance['total'] ?? '…' }}.</strong>
                Cada factura se consulta en el SRI. Te aviso en la campana al terminar.</p>
        </div>
    @elseif (! empty($avance['falla']))
        <div class="ct-aviso"><p><strong>Se detuvo:</strong> {{ $avance['falla'] }}. Lo traído quedó; vuelve a tocar «Traer el detalle» en «Por traer del SRI».</p></div>
    @endif

    {{-- ── Por registrar y sin detalle: las que faltan ─────────────────── --}}
    @if (in_array($vista, ['por_registrar', 'sin_detalle'], true))
        @if ($comprobantes->isEmpty() && $ventas->isEmpty())
            <section class="ct-panel">
                <p class="ct-fila-s" style="padding:18px">
                    @if ($vista === 'por_registrar')
                        No hay facturas por traer{{ $periodo }}. Para traer otro mes, usa «Subir archivo del SRI» arriba.
                    @else
                        No hay facturas sin detalle{{ $periodo }}: el SRI entregó el de todas.
                    @endif
                </p>
            </section>
        @endif

        {{-- Sin detalle no se registra: aquí se sube su XML. --}}
        @if ($vista === 'sin_detalle')
            @livewire(\App\Livewire\CompletarFacturas::class, ['proveedor' => $proveedor, 'anio' => $anio, 'mes' => $mes, 'conLista' => false], key('subir-xml'))
        @endif

        @if ($comprobantes->isNotEmpty())
            <section class="ct-panel">
                <header>
                    <div>
                        <h2 class="ct-tit">{{ $vista === 'por_registrar' ? 'Compras por traer del SRI' : 'Compras sin detalle' }}</h2>
                        <p class="ct-pie2">
                            @if ($vista === 'por_registrar')
                                Al subir el archivo del portal se traen solas. Las que tengan detalle pasan a «Listas para registrar»; las que el SRI ya no entregue, a «Falta el detalle»
                            @else
                                Sin detalle no se sabe qué fue la factura: sube su XML arriba (el portal del SRI lo deja bajar en «Comprobantes recibidos»)
                            @endif
                            @if ($conteos[$vista] > 120) · se muestran las primeras 120 @endif
                        </p>
                    </div>
                    @if ($vista === 'por_registrar' && ! ($avance['corriendo'] ?? false))
                        <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                                wire:click="traerDetalle" wire:loading.attr="disabled" wire:target="traerDetalle">
                            Traer el detalle de {{ $conteos['por_registrar'] === 1 ? 'la factura' : "las {$conteos['por_registrar']}" }}{{ $proveedor ? ' de este proveedor' : '' }}
                        </button>
                    @endif
                    {{-- El SRI pudo no responder en su momento: se le vuelve a preguntar, por lote. --}}
                    @if ($vista === 'sin_detalle' && $porReconsultar['todas'] > 0 && ! ($avance['corriendo'] ?? false))
                        @php $filtrado = $anio || $proveedor; @endphp
                        <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end">
                            @if ($filtrado && $porReconsultar['filtro'] > 0)
                                <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                                        wire:click="volverAConsultar(false)" wire:loading.attr="disabled" wire:target="volverAConsultar">
                                    Consultar al SRI {{ $porReconsultar['filtro'] === 1 ? 'la' : "las {$porReconsultar['filtro']}" }}{{ $periodo }}{{ $proveedor ? ' de este proveedor' : '' }}
                                </button>
                            @endif
                            <button type="button" class="{{ $filtrado ? 'ct-pestana' : 'ct-btn-descarga' }}" style="{{ $filtrado ? '' : 'border:0;' }}cursor:pointer"
                                    wire:click="volverAConsultar(true)" wire:loading.attr="disabled" wire:target="volverAConsultar">
                                Consultar al SRI {{ $porReconsultar['todas'] === 1 ? 'la única' : "todas ({$porReconsultar['todas']})" }}
                            </button>
                        </div>
                    @endif
                </header>

                @foreach ($comprobantes as $c)
                    <div class="ct-fila" style="align-items:flex-start; flex-wrap:wrap; gap:10px" wire:key="c-{{ $c->id }}">
                        <div style="min-width:230px; flex:1">
                            <p class="ct-fila-t">{{ $c->razon_social ?: $c->identificacion }}</p>
                            <p class="ct-fila-s">
                                {{ $c->numero }} · {{ $c->fecha_emision->format('d/m/Y') }} ·
                                base {{ $money($c->base) }} · IVA {{ $money($c->iva) }} ·
                                <strong>{{ $money($c->total) }}</strong>
                            </p>
                            <p style="margin:6px 0 0">
                                @if ($vista === 'sin_detalle')
                                    <span class="ct-chip ct-chip-wa">Falta el detalle · sube su XML</span>
                                @else
                                    <span class="ct-chip ct-chip-n">Por traer del SRI</span>
                                @endif
                            </p>
                        </div>

                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; justify-content:flex-end">
                            @if ($vista === 'sin_detalle' && $c->desglose === 'sin_xml')
                                <button type="button" class="ct-pestana" wire:click="volverAConsultarUna({{ $c->id }})"
                                        wire:loading.attr="disabled" wire:target="volverAConsultarUna({{ $c->id }})">
                                    <span wire:loading.remove wire:target="volverAConsultarUna({{ $c->id }})">Consultar al SRI</span>
                                    <span wire:loading wire:target="volverAConsultarUna({{ $c->id }})">Consultando…</span>
                                </button>
                            @endif
                            <button type="button" class="ct-pestana" wire:click="personal({{ $c->id }})"
                                    wire:confirm="¿Es una compra personal? No se registra como gasto ni mueve dinero de la empresa: solo su IVA suma al 104.">
                                No es de la empresa
                            </button>
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
                    @php $manual = $vista === 'sin_detalle'; @endphp
                    <div class="ct-fila" style="align-items:flex-start; flex-wrap:wrap; gap:10px" wire:key="v-{{ $c->id }}">
                        <div style="min-width:230px; flex:1">
                            <p class="ct-fila-t">{{ $c->razon_social ?: $c->identificacion }}</p>
                            <p class="ct-fila-s">{{ $c->numero }} · {{ $c->fecha_emision->format('d/m/Y') }} ·
                                <strong>{{ $money($c->total) }}</strong></p>
                            <p style="margin:6px 0 0">
                                <span class="ct-chip {{ $manual ? 'ct-chip-wa' : 'ct-chip-n' }}">
                                    {{ $manual ? 'Falta el detalle · sube su XML' : 'Detalle: se trae del SRI' }}
                                </span>
                            </p>
                        </div>

                        <div style="display:flex; gap:8px; align-items:center; flex:1 1 460px; flex-wrap:wrap">
                            @if (! $manual)
                                <select wire:model="eleccion.{{ $c->id }}.pago" class="ct-select" style="flex:1 1 200px" aria-label="Cómo se cobró">
                                    @foreach ($mediosVenta as $valor => $etiqueta)
                                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
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

    {{-- ── Listas para registrar: con su detalle; se elige el pago y se registran ── --}}
    @if ($vista === 'esperan')
        @livewire(\App\Livewire\CompletarFacturas::class, ['proveedor' => $proveedor, 'anio' => $anio, 'mes' => $mes], key('listas'))
    @endif

    {{-- ── Registradas: con lo que se registró ───────────────────────────── --}}
    @if ($vista === 'registradas')
        <section class="ct-panel">
            <header>
                <div>
                    <h2 class="ct-tit">Facturas registradas</h2>
                    <p class="ct-pie2">
                        Las registradas a mano antes se completan subiendo su XML (pestaña «Falta el detalle»): toman el detalle real y se rehace su asiento
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
                    Todavía no hay facturas registradas{{ $periodo }}.
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
                        <p class="ct-fila-s">{{ $m->num_comprobantes }} {{ $m->num_comprobantes == 1 ? 'comprobante' : 'comprobantes' }} del portal
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
