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

    {{-- Lo pedido: se refresca solo mientras haya algo en la cola --}}
    <section class="ct-panel"
             @if ($pedidos->whereIn('estado', ['pendiente', 'procesando'])->isNotEmpty())
                 wire:poll.10s
             @endif>
        <header>
            <h2 class="ct-tit">Declaraciones pedidas</h2>
            <span class="ct-pie2">
                {{ $pedidos->whereIn('estado', ['pendiente', 'procesando'])->count() }} en cola
            </span>
        </header>

        @forelse ($pedidos as $d)
            @php
                $tono = match ($d->estado) {
                    'listo' => 'ok', 'error' => 'da', 'procesando' => 'wa', default => 'n',
                };
                $texto = match ($d->estado) {
                    'listo' => 'Listo', 'error' => 'Falló',
                    'procesando' => 'Generando…', default => 'En cola',
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
                    @if ($d->mensaje)
                        <p class="ct-fila-s" style="color:var(--da)">{{ $d->mensaje }}</p>
                    @endif
                </div>
                <div style="display:flex; align-items:center; gap:10px; flex-shrink:0">
                    @if ($d->archivo)
                        <a class="ct-btn-descarga" href="{{ route('contabilidad.declaracion', $d->id) }}">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            Descargar XML
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
        <header>
            <h2 class="ct-tit">Periodos</h2>
            <span class="ct-pie2">{{ $empresa->name }}</span>
        </header>
        <table class="ct-tabla">
            <thead>
                <tr><th>Periodo</th><th>Documento</th><th style="text-align:right">Valor</th><th>Comprobantes</th><th>Estado</th></tr>
            </thead>
            <tbody>
            @foreach ($periodos as $p)
                <tr class="fuerte">
                    <td data-col="Periodo">{{ ucfirst($p['etiqueta']) }}</td>
                    <td data-col="Documento">Formulario 104 · IVA</td>
                    <td class="num" data-col="Valor">{{ $money($p['iva']) }}</td>
                    <td data-col="Comprobantes">{{ $p['comprobantes'] }}</td>
                    <td data-col="Estado"><span class="ct-chip ct-chip-wa">Por generar</span></td>
                </tr>
                <tr>
                    <td data-col="Periodo"></td>
                    <td data-col="Documento">Formulario 103 · Retenciones</td>
                    <td class="num" data-col="Valor">{{ $money($p['retenido']) }}</td>
                    <td data-col="Comprobantes">—</td>
                    <td data-col="Estado">
                        <span class="ct-chip ct-chip-{{ $p['retenido'] > 0 ? 'wa' : 'n' }}">
                            {{ $p['retenido'] > 0 ? 'Por generar' : 'Sin retenciones' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <td data-col="Periodo"></td>
                    <td data-col="Documento">Anexo transaccional</td>
                    <td class="num" data-col="Valor">—</td>
                    <td data-col="Comprobantes">{{ $p['comprobantes'] }}</td>
                    <td data-col="Estado"><span class="ct-chip ct-chip-wa">Por generar</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>

    <section class="ct-panel">
        <header><h2 class="ct-tit">Qué falta para cerrar el ciclo</h2></header>
        @foreach ([
            ['Formulario 104', 'los casilleros salen de ventas, compras, gastos y retenciones del mes', 'Funcionando', 'ok'],
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
