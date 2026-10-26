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

    {{-- Lo que hay que hacer ahora, ya abierto: el mes que toca declarar, paso a paso. --}}
    @if ($ahora)
        @php
            $al = $ahora['alerta'];
            $dAhora = $ahora['declaracion'];
            $cuando = match (true) {
                $al['dias'] === null => 'sin fecha de vencimiento: falta el RUC',
                $al['dias'] < 0      => 'venció el ' . $al['vence']->format('d/m/Y'),
                $al['dias'] === 0    => 'vence hoy',
                default              => 'vence el ' . $al['vence']->format('d/m/Y') . ' · faltan ' . $al['dias'] . ' días',
            };
        @endphp
        <section class="ct-panel ct-ahora {{ $al['urgente'] ? 'es-urgente' : '' }}"
                 @if (in_array($dAhora?->estado, ['pendiente', 'procesando'], true)) wire:poll.5s @endif>
            <header>
                <div>
                    <h2 class="ct-ahora-tit">Declara el IVA de {{ $al['etiqueta'] }}</h2>
                    <p class="ct-pie2">{{ $cuando }}</p>
                </div>
            </header>

            <div class="ct-declarar">
                @if (! $dAhora || ! in_array($dAhora->estado, ['listo', 'pendiente', 'procesando'], true))
                    <p class="ct-paso"><span>1</span> Calcula la declaración con lo registrado en el ERP.</p>
                    <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                            wire:click="pedir({{ $al['anio'] }}, {{ $al['mes'] }})" wire:loading.attr="disabled" wire:target="pedir">
                        Calcular la declaración
                    </button>
                @elseif ($dAhora->estado !== 'listo')
                    <p class="ct-paso"><span>1</span> Calculando la declaración… esta página se actualiza sola.</p>
                @else
                    <p class="ct-paso"><span>1</span>
                        Entra a <a href="https://srienlinea.sri.gob.ec" target="_blank" rel="noopener">SRI en línea</a>
                        con el RUC y la clave de la empresa, y abre la declaración del IVA de {{ $al['etiqueta'] }}.
                    </p>
                    <p class="ct-paso"><span>2</span> Escribe en el formulario estas casillas. Las que no aparecen aquí se quedan en cero.</p>

                    @include('filament.contabilidad.parciales.casillas-104', ['guia' => $ahora['guia']])

                    <p class="ct-paso" style="margin-top:16px"><span>3</span> Antes de enviar, los totales del portal deben ser los de arriba. Si uno no coincide, revisa lo que escribiste.</p>
                    <p class="ct-paso"><span>4</span> Envía la declaración, descarga el comprobante en PDF y súbelo aquí. Con eso el mes queda presentado y este aviso se apaga.</p>
                    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:10px">
                        <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                                wire:click="mountAction('subirComprobante', { id: {{ $dAhora->id }} })">
                            Subir comprobante del SRI
                        </button>
                        <button type="button" class="ct-pestana" wire:click="pedir({{ $al['anio'] }}, {{ $al['mes'] }})"
                                wire:loading.attr="disabled" wire:target="pedir"
                                title="Si registraste facturas después de calcular, vuelve a calcular antes de declarar">
                            Volver a calcular
                        </button>
                    </div>
                @endif
            </div>
        </section>
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


    {{-- Período a período: lo que de verdad se puede auditar. Se refresca solo mientras algo se calcula. --}}
    <section class="ct-panel"
             @if (collect($periodos)->contains(fn ($p) => in_array($p['declaracion']?->estado, ['pendiente', 'procesando'], true)))
                 wire:poll.5s
             @endif>
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
                            ['Ventas netas', $r['ventas_netas']],
                            ['Compras netas', $r['compras_netas']],
                            ['IVA en ventas · lo calcula el portal', $r['iva_ventas']],
                            ['IVA en compras · lo calcula el portal', $r['iva_compras']],
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
                        <button type="button" class="ct-pestana" wire:click="pedir({{ $p['anio'] }}, {{ $p['mes'] }})"
                                wire:loading.attr="disabled" wire:target="pedir"
                                title="Vuelve a calcular el mes, por ejemplo después de registrar facturas que faltaban">
                            Volver a generar
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

                    @include('filament.contabilidad.parciales.casillas-104', ['guia' => $declarar])

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
