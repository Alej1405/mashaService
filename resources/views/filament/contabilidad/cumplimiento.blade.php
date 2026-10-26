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
                    {{-- Lo que generó el sistema, antes de ir al portal --}}
                    <div class="ct-resumen">
                        @foreach ([
                            ['IVA en ventas', $r['iva_ventas']],
                            ['IVA en compras', $r['iva_compras']],
                            ['Crédito aplicado', $r['credito_mes']],
                            [$r['a_pagar'] > 0 ? 'A pagar' : 'A favor', $r['a_pagar'] > 0 ? $r['a_pagar'] : $r['a_favor']],
                            ['Crédito para el próximo mes', $r['arrastra']],
                        ] as [$etiqueta, $valor])
                            <div>
                                <span class="ct-resumen-n">{{ $money($valor) }}</span>
                                <span class="ct-resumen-e">{{ $etiqueta }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div style="display:flex; align-items:center; gap:10px; flex-shrink:0; flex-wrap:wrap; justify-content:flex-end">
                    @if ($d?->estado === 'listo' && ! $d->presentado_en)
                        <button type="button" class="ct-pestana {{ $declarar && $declarar['id'] === $d->id ? 'es-activa' : '' }}"
                                aria-expanded="{{ $declarar && $declarar['id'] === $d->id ? 'true' : 'false' }}"
                                wire:click="verDeclarar({{ $d->id }})">
                            {{ $declarar && $declarar['id'] === $d->id ? 'Ocultar' : 'Qué declarar' }}
                        </button>
                        <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                                wire:click="mountAction('subirComprobante', { id: {{ $d->id }} })">
                            Subir comprobante del SRI
                        </button>
                    @elseif ($d?->presentado_en && ($d->datos['comprobante'] ?? null))
                        <span class="ct-fila-s">
                            serial {{ $d->datos['comprobante']['serial'] }}
                            @if ($d->datos['comprobante']['codigo_verificador'] ?? null)
                                · verificador {{ $d->datos['comprobante']['codigo_verificador'] }}
                            @endif
                        </span>
                    @elseif (! $d)
                        <button type="button" class="ct-chip ct-chip-n" style="border:0;cursor:pointer"
                                wire:click="pedir({{ $p['anio'] }}, {{ $p['mes'] }})">
                            Generar
                        </button>
                    @endif

                    <span class="ct-chip ct-chip-{{ $p['estado']['tono'] }}">{{ $p['estado']['texto'] }}</span>
                </div>
            </div>

            {{-- Qué escribir en el portal: solo lo que lleva valor, en el orden del formulario. --}}
            @if ($declarar && $d && $declarar['id'] === $d->id)
                <div class="ct-declarar">
                    <ol class="ct-pasos">
                        <li>En el portal del SRI abre la declaración del IVA de {{ $p['etiqueta'] }}.</li>
                        <li>Escribe estas casillas. Las demás se quedan en cero.</li>
                        <li>Antes de enviar, el portal debe mostrarte los totales de abajo.</li>
                        <li>Al terminar, sube aquí el comprobante en PDF.</li>
                    </ol>

                    @forelse ($declarar['casillas'] as $seccion => $filas)
                        <p class="ct-tit" style="margin:14px 0 6px">{{ $seccion }}</p>
                        @foreach ($filas as $f)
                            @php $valor = $f['conteo'] ? (string) (int) $f['valor'] : number_format($f['valor'], 2, '.', ''); @endphp
                            <div class="ct-casilla" x-data="{ copiado: false }">
                                <span class="ct-casilla-n">{{ $f['codigo'] }}</span>
                                <span class="ct-casilla-t">{{ $f['nombre'] }}</span>
                                <span class="ct-casilla-v">{{ $f['conteo'] ? $valor : $money($f['valor']) }}</span>
                                <button type="button" class="ct-pestana"
                                        x-on:click="navigator.clipboard.writeText('{{ $valor }}'); copiado = true; setTimeout(() => copiado = false, 1500)"
                                        :aria-label="'Copiar casilla {{ $f['codigo'] }}'">
                                    <span x-text="copiado ? 'Copiado' : 'Copiar'"></span>
                                </button>
                            </div>
                        @endforeach
                    @empty
                        <p class="ct-fila-s">Mes sin movimiento: se declara en cero, sin escribir ninguna casilla.</p>
                    @endforelse

                    <p class="ct-tit" style="margin:18px 0 6px">El portal debe mostrarte</p>
                    @foreach ($declarar['comparar'] as [$nombre, $codigo, $valor])
                        <div class="ct-casilla ct-casilla-total">
                            <span class="ct-casilla-n">{{ $codigo }}</span>
                            <span class="ct-casilla-t">{{ $nombre }}</span>
                            <span class="ct-casilla-v">{{ $money($valor) }}</span>
                        </div>
                    @endforeach
                    <p class="ct-pie2" style="margin-top:8px">Si un total no coincide, revisa la casilla que escribiste antes de enviar: el comprobante se compara con estos valores.</p>
                </div>
            @endif
        @empty
            <p class="ct-vacio">
                Marca el inicio de gestión para ver los períodos por los que responde el sistema.
            </p>
        @endforelse
    </section>

    <section class="ct-panel">
        <header><h2 class="ct-tit">Qué cuenta como cumplido</h2></header>
        @foreach ([
            ['Generado', 'los valores están calculados: «Qué declarar» dice qué escribir en el portal', 'wa'],
            ['Presentado', 'se subió el comprobante del SRI y cuadra con lo generado — esto es cumplir', 'ok'],
            ['Vencido', 'pasó la fecha del noveno dígito y no hay comprobante', 'da'],
        ] as [$titulo, $detalle, $tono])
            <div class="ct-fila">
                <div><p class="ct-fila-t">{{ $titulo }}</p><p class="ct-fila-s">{{ $detalle }}</p></div>
                <span class="ct-chip ct-chip-{{ $tono }}">{{ $titulo }}</span>
            </div>
        @endforeach
    </section>
</div>
</x-filament-panels::page>
