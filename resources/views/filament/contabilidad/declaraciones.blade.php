<x-filament-panels::page>
@include('filament.contabilidad._estilos')
@php $money = fn ($n) => '$ ' . number_format((float) $n, 2, ',', '.'); @endphp

<div class="ct">
    <div class="ct-aviso" style="background:{{ $servicio['arriba'] ? '#ecfdf5' : '#fffbeb' }};border-color:{{ $servicio['arriba'] ? '#a7f3d0' : '#fde68a' }}">
        <p style="color:{{ $servicio['arriba'] ? '#047857' : '#b45309' }}">
            <strong>Servicio de declaraciones:</strong>
            {{ $servicio['arriba'] ? 'conectado · ' : 'no disponible · ' }}{{ $servicio['detalle'] }}.
            @unless ($servicio['arriba'])
                La contabilidad del ERP funciona igual; solo no se puede generar el archivo.
            @endunless
        </p>
    </div>

    {{-- El rastro del crédito tributario, que es lo que da el historial --}}
    @if (($cadena['periodos'] ?? 0) > 0)
        <div class="ct-aviso"
             style="background:{{ ($cadena['cadena_cuadra'] ?? true) ? '#ecfdf5' : '#fffbeb' }};
                    border-color:{{ ($cadena['cadena_cuadra'] ?? true) ? '#a7f3d0' : '#fde68a' }}">
            <p style="color:{{ ($cadena['cadena_cuadra'] ?? true) ? 'var(--ok)' : 'var(--wa)' }}">
                <strong>{{ $cadena['periodos'] }} declaraciones en el histórico.</strong>
                @if ($cadena['cadena_cuadra'] ?? true)
                    El crédito tributario encadena sin saltos:
                    <strong>{{ $money($cadena['credito_actual'] ?? 0) }}</strong> a favor
                    al {{ $cadena['ultimo_periodo'] ?? '—' }}.
                @else
                    El crédito no encadena en {{ count($cadena['saltos'] ?? []) }} puntos:
                    @foreach (array_slice($cadena['saltos'] ?? [], 0, 3) as $s)
                        de {{ $s['desde'] }} salen {{ $money($s['sale']) }} y en {{ $s['hasta'] }}
                        entran {{ $money($s['entra']) }}{{ !$loop->last ? ';' : '.' }}
                    @endforeach
                    Falta alguna declaración por cargar.
                @endif
            </p>
        </div>
    @endif

    {{-- El ATS, mes por mes: lo pendiente arriba, con sus pasos; verde solo lo presentado --}}
    @php
        $pendientesAts = collect($mesesAts)->filter(fn ($m) => ! $m['ats']?->presentado_en);
        $presentadosAts = collect($mesesAts)->filter(fn ($m) => $m['ats']?->presentado_en)->reverse();
        $generando = $pendientesAts->contains(fn ($m) => in_array($m['ats']?->estado, ['pendiente', 'procesando'], true));
    @endphp
    <section class="ct-panel" @if ($generando) wire:poll.10s @endif>
        <header>
            <div>
                <h2 class="ct-tit">Anexo transaccional · {{ $anioAts }}</h2>
                <p class="ct-pie2">
                    {{ $pendientesAts->count() }} {{ $pendientesAts->count() === 1 ? 'mes pendiente' : 'meses pendientes' }}
                    · {{ $presentadosAts->count() }} presentados
                </p>
            </div>
            @if (count($aniosAts) > 1)
                <div class="ct-tabs" role="tablist" aria-label="Año">
                    @foreach ($aniosAts as $a)
                        <button type="button" role="tab" aria-selected="{{ $anioAts === $a ? 'true' : 'false' }}"
                                class="ct-pestana {{ $anioAts === $a ? 'es-activa' : '' }}"
                                wire:click="$set('anioAts', {{ $a }})">{{ $a }}</button>
                    @endforeach
                </div>
            @endif
        </header>

        @forelse ($pendientesAts as $m)
            @php
                [$tono, $texto] = match (true) {
                    in_array($m['ats']?->estado, ['pendiente', 'procesando'], true) => ['wa', 'Generando…'],
                    $m['ats']?->estado === 'listo' => ['wa', 'Listo para descargar'],
                    $m['ats']?->estado === 'error' => ['da', 'Falló'],
                    default => ['da', 'Sin generar'],
                };
            @endphp
            <div class="ct-fila" style="align-items:flex-start">
                <div style="min-width:0; flex:1">
                    <p class="ct-fila-t">{{ $m['etiqueta'] }}</p>
                    <p class="ct-fila-s">
                        @if ($m['f104']?->presentado_en)
                            104 presentado · serial {{ $m['f104']->comprobante_presentacion ?? '—' }}
                            @unless ($m['f104']->archivo_pdf) · <span style="color:var(--wa)">sin constancia en PDF</span> @endunless
                        @else
                            <span style="color:var(--wa)">104 sin presentar</span>
                        @endif
                    </p>
                    <div style="margin-top:6px">
                        @include('filament.contabilidad.parciales.ats-pasos', ['ats' => $m['ats'], 'anio' => $m['anio'], 'mes' => $m['mes'], 'etiqueta' => $m['etiqueta']])
                    </div>
                </div>
                <span class="ct-chip ct-chip-{{ $tono }}" style="flex-shrink:0">{{ $texto }}</span>
            </div>
        @empty
            <p class="ct-vacio">Todos los ATS de {{ $anioAts }} están presentados.</p>
        @endforelse

        @foreach ($presentadosAts as $m)
            <div class="ct-fila">
                <div style="min-width:0">
                    <p class="ct-fila-t">{{ $m['etiqueta'] }}</p>
                    <p class="ct-fila-s">ATS presentado el {{ $m['ats']->presentado_en->format('d/m/Y') }}
                        @if ($m['ats']->archivo_pdf) · confirmación del SRI guardada @endif</p>
                </div>
                <div style="display:flex; align-items:center; gap:10px; flex-shrink:0">
                    @if ($m['ats']->archivo)
                        <a class="ct-pestana" href="{{ route('contabilidad.declaracion', $m['ats']->id) }}">Descargar el presentado</a>
                    @endif
                    <span class="ct-chip ct-chip-ok">Presentado</span>
                </div>
            </div>
        @endforeach
    </section>

    {{-- Lo pedido: se refresca solo mientras haya algo en la cola --}}
    <section class="ct-panel"
             @if ($pedidos->whereIn('estado', ['pendiente', 'procesando'])->isNotEmpty())
                 wire:poll.10s
             @endif>
        <header>
            <h2 class="ct-tit">Otras declaraciones</h2>
            <span class="ct-pie2">
                {{ $pedidos->whereIn('estado', ['pendiente', 'procesando'])->count() }} en cola
            </span>
        </header>

        @forelse ($pedidos as $d)
            @php
                // Generado no es presentado: el chip dice cuál de los dos es.
                $tono = match (true) {
                    $d->presentado_en !== null => 'ok', $d->estado === 'listo' => 'wa',
                    $d->estado === 'error' => 'da', $d->estado === 'procesando' => 'wa', default => 'n',
                };
                $texto = match (true) {
                    $d->presentado_en !== null => 'Presentado', $d->estado === 'listo' => 'Falta presentar',
                    $d->estado === 'error' => 'Falló', $d->estado === 'procesando' => 'Generando…', default => 'En cola',
                };
            @endphp
            <div class="ct-fila">
                <div style="min-width:0">
                    <p class="ct-fila-t">
                        {{ \App\Models\Declaracion::TIPOS[$d->tipo] ?? $d->tipo }} · {{ $d->periodo }}
                    </p>
                    <p class="ct-fila-s">
                        @if ($d->es_cargada)
                            cargada del portal · serial {{ $d->comprobante_presentacion ?? '—' }}
                        @else
                            seguimiento {{ \Illuminate\Support\Str::limit($d->seguimiento, 8, '') }}
                        @endif
                        @if ($d->generado_en) · {{ $d->generado_en->diffForHumans() }} @endif
                        @if ($d->estado === 'listo' && $d->casillero('999') > 0)
                            · a pagar {{ $money($d->casillero('999')) }}
                        @elseif ($d->estado === 'listo')
                            · sin valor a pagar
                        @endif
                    </p>
                    @foreach ($d->avisos ?? [] as $aviso)
                        <p class="ct-fila-s" style="color:var(--wa)">{{ $aviso }}</p>
                    @endforeach
                    @if ($d->mensaje && $d->tipo !== 'ats')
                        <p class="ct-fila-s" style="color:var(--da)">{{ $d->mensaje }}</p>
                    @endif
                </div>
                <div style="display:flex; align-items:center; gap:10px; flex-shrink:0">
                    @if ($d->tipo === 'f104' && $d->estado === 'listo' && ! $d->presentado_en)
                        <a class="ct-btn-descarga" href="{{ \App\Filament\Contabilidad\Pages\Cumplimiento::getUrl() }}">Qué declarar</a>
                    @endif
                    {{-- El ATS se descarga dentro de sus pasos; el 104 se escribe en el portal. Aquí queda el resto. --}}
                    @if ($d->archivo && ! in_array($d->tipo, ['f104', 'ats'], true))
                        <a class="ct-btn-descarga" href="{{ route('contabilidad.declaracion', $d->id) }}">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            Descargar {{ $d->tipo === 'ats' ? sprintf('AT%02d%d.zip', $d->mes, $d->anio) : 'XML' }}
                        </a>
                    @endif
                    <span class="ct-chip ct-chip-{{ $tono }}">{{ $texto }}</span>
                </div>
            </div>
        @empty
            <p class="ct-vacio">Todavía no has pedido ninguna. Usa los botones de arriba.</p>
        @endforelse
    </section>

    <section class="ct-panel">
        <header>
            <h2 class="ct-tit">Formulario 101 · ejercicio {{ $anio101 }}</h2>
            <span class="ct-pie2">{{ count($conceptos) }} casilleros salen de los saldos del ERP</span>
        </header>
        @if (count($conceptos))
            <table class="ct-tabla">
                <thead><tr><th>Casillero</th><th>Concepto</th><th style="text-align:right">Valor</th></tr></thead>
                <tbody>
                @foreach ($conceptos as $c)
                    <tr>
                        <td data-col="Casillero" style="font-family:ui-monospace,monospace">{{ $c['concepto'] }}</td>
                        <td data-col="Concepto">{{ $c['etiqueta'] }}</td>
                        <td class="num" data-col="Valor">$ {{ number_format((float) $c['valor'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <p style="padding:26px 18px;text-align:center;color:#64748b">
                Sin asientos confirmados en {{ $anio101 }}: no hay casilleros que declarar todavía.
            </p>
        @endif
    </section>

    <section class="ct-panel">
        <header><h2 class="ct-tit">Qué falta para cerrar el ciclo</h2></header>
        @foreach ([
            ['Formulario 104', 'se declara en Cumplimiento: las casillas a escribir en el portal y el comprobante del SRI que lo cierra', 'Funcionando', 'ok'],
            ['Anexo transaccional', 'un registro por comprobante, con sustento y forma de pago', 'Funcionando', 'ok'],
            ['Formulario 103', 'retenciones en la fuente de renta', 'Pendiente', 'wa'],
            ['Firma electrónica', 'certificado de la empresa para firmar el anexo antes de subirlo', 'Por definir', 'n'],
        ] as [$t, $sub, $chip, $tono])
            <div class="ct-fila">
                <div><p class="ct-fila-t">{{ $t }}</p><p class="ct-fila-s">{{ $sub }}</p></div>
                <span class="ct-chip ct-chip-{{ $tono }}">{{ $chip }}</span>
            </div>
        @endforeach
    </section>
</div>
</x-filament-panels::page>
