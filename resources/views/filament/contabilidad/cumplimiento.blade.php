<x-filament-panels::page>
@include('filament.contabilidad._estilos')
@php $money = fn ($n) => '$ ' . number_format((float) $n, 2, ',', '.'); @endphp

<div class="ct">
    @if (! $cumplimiento['desde'])
        <div class="ct-aviso">
            <p>
                <strong>Falta marcar el inicio de gestión.</strong>
                Sin esa fecha no se puede decir desde cuándo el ERP responde por las declaraciones
                de la empresa, ni por tanto qué está pendiente.
            </p>
        </div>
    @endif

    {{-- La respuesta a "¿estamos al día?" --}}
    <div class="ct-rej-4">
        <div class="ct-card">
            <div class="ct-card-cab">
                <span class="ct-tit">Estado</span>
                <span class="ct-chip ct-chip-{{ $cumplimiento['al_dia'] ? 'ok' : 'da' }}">
                    {{ $cumplimiento['al_dia'] ? 'Al día' : 'Con atrasos' }}
                </span>
            </div>
            <p class="ct-val">{{ $cumplimiento['presentados'] }}/{{ $cumplimiento['total'] }}</p>
            <p class="ct-tit" style="margin-top:7px">Períodos presentados</p>
            <p class="ct-pie2">desde {{ $cumplimiento['desde'] ?? 'sin definir' }}</p>
        </div>

        <div class="ct-card">
            <p class="ct-val">{{ $cumplimiento['vencidos'] }}</p>
            <p class="ct-tit" style="margin-top:7px">Vencidos sin generar</p>
            <p class="ct-pie2">{{ $cumplimiento['vencidos'] ? 'pasó la fecha del noveno dígito' : 'ninguno fuera de plazo' }}</p>
        </div>

        <div class="ct-card">
            <p class="ct-val">{{ $cumplimiento['sin_movimiento'] }}</p>
            <p class="ct-tit" style="margin-top:7px">Meses sin movimiento</p>
            <p class="ct-pie2">se declaran en cero igual</p>
        </div>

        <div class="ct-card">
            <p class="ct-val">{{ $importados }}</p>
            <p class="ct-tit" style="margin-top:7px">Comprobantes del portal</p>
            <p class="ct-pie2">{{ $importados ? 'importados del SRI' : 'sin importar nada todavía' }}</p>
        </div>
    </div>


    {{-- Período a período: lo que de verdad se puede auditar --}}
    <section class="ct-panel">
        <header>
            <h2 class="ct-tit">Períodos · formulario 104</h2>
            <span class="ct-pie2">el vencimiento sale del noveno dígito del RUC</span>
        </header>

        @forelse ($periodos as $p)
            @php
                $d = $p['declaracion'];
                $mov = $p['movimiento'];
                $vencido = $p['vence'] && now()->greaterThan($p['vence']) && ! $d?->presentado_en;
            @endphp
            <div class="ct-fila">
                <div style="min-width:0">
                    <p class="ct-fila-t">{{ ucfirst($p['etiqueta']) }}</p>
                    <p class="ct-fila-s">
                        @if ($mov['vacio'])
                            sin movimiento registrado · se declara en cero
                        @else
                            {{ $mov['ventas'] }} ventas · {{ $mov['compras'] }} compras
                            @if ($mov['importados']) · {{ $mov['importados'] }} del portal @endif
                        @endif
                        @if ($p['vence'])
                            · vence {{ $p['vence']->format('d/m/Y') }}
                        @endif
                        @if ($d?->descargado_en)
                            · descargado {{ $d->descargado_en->format('d/m/Y') }}
                        @endif
                    </p>
                </div>

                @if ($d?->estado === 'listo' && $d->datos)
                    @php $r = $this->resumenDe($d->id); @endphp
                    {{-- Antes de descargar: qué generó el sistema --}}
                    <div class="ct-resumen">
                        @foreach ([
                            ['IVA en ventas', $r['iva_ventas']],
                            ['IVA en compras', $r['iva_compras']],
                            ['Crédito aplicado', $r['credito_mes']],
                            [$r['a_pagar'] > 0 ? 'A pagar' : 'A favor', $r['a_pagar'] > 0 ? $r['a_pagar'] : $r['a_favor']],
                            ['Arrastra al mes siguiente', $r['arrastra']],
                        ] as [$etiqueta, $valor])
                            <div>
                                <span class="ct-resumen-n">{{ $money($valor) }}</span>
                                <span class="ct-resumen-e">{{ $etiqueta }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div style="display:flex; align-items:center; gap:10px; flex-shrink:0">
                    @if ($d?->archivo)
                        <a class="ct-btn-descarga" href="{{ route('contabilidad.declaracion', $d->id) }}">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                            </svg>
                            XML
                        </a>
                    @endif

                    @if ($d?->estado === 'listo' && ! $d->presentado_en)
                        <button type="button" class="ct-chip ct-chip-n" style="border:0;cursor:pointer"
                                wire:click="marcarPresentado({{ $d->id }})">
                            Marcar presentado
                        </button>
                    @elseif (! $d)
                        <button type="button" class="ct-chip ct-chip-n" style="border:0;cursor:pointer"
                                wire:click="pedir({{ $p['anio'] }}, {{ $p['mes'] }})">
                            Generar
                        </button>
                    @endif

                    <span class="ct-chip ct-chip-{{ $p['estado']['tono'] }}">{{ $p['estado']['texto'] }}</span>
                </div>
            </div>
        @empty
            <p class="ct-vacio">
                Marca el inicio de gestión para ver los períodos por los que responde el sistema.
            </p>
        @endforelse
    </section>

    <section class="ct-panel">
        <header><h2 class="ct-tit">Qué cuenta como cumplido</h2></header>
        @foreach ([
            ['Generado', 'el archivo existe en el sistema', 'wa'],
            ['Descargado', 'alguien se lo llevó para subirlo al portal', 'wa'],
            ['Presentado', 'consta la fecha en que se subió al SRI — esto es cumplir', 'ok'],
            ['Vencido', 'pasó la fecha del noveno dígito y no hay nada generado', 'da'],
        ] as [$titulo, $detalle, $tono])
            <div class="ct-fila">
                <div><p class="ct-fila-t">{{ $titulo }}</p><p class="ct-fila-s">{{ $detalle }}</p></div>
                <span class="ct-chip ct-chip-{{ $tono }}">{{ $titulo }}</span>
            </div>
        @endforeach
    </section>
</div>
</x-filament-panels::page>
