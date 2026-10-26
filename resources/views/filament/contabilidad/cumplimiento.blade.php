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
        @php $calculando = collect([$dAhora, $ahora['ats']])->contains(fn ($x) => in_array($x?->estado, ['pendiente', 'procesando'], true)); @endphp
        <section class="ct-panel ct-ahora {{ $al['urgente'] ? 'es-urgente' : '' }}" @if ($calculando) wire:poll.5s @endif>
            <header>
                <div>
                    <h2 class="ct-ahora-tit">Declara el IVA{{ $ahora['debe_ats'] ? ' y el ATS' : '' }} de {{ $al['etiqueta'] }}</h2>
                    <p class="ct-pie2">{{ $cuando }} · el mes queda al día cuando {{ $ahora['debe_ats'] ? 'los dos estén presentados' : 'esté presentado' }}</p>
                </div>
            </header>

            <div class="ct-declarar">
              <div class="ct-parte">
                <p class="ct-parte-tit">IVA · formulario 104</p>
                @if ($dAhora?->presentado_en)
                    <p class="ct-paso ct-paso-hecho"><b>✓</b><span>IVA presentado el {{ $dAhora->presentado_en->format('d/m/Y') }}
                        @if ($dAhora->comprobante_presentacion) · serial {{ $dAhora->comprobante_presentacion }} @endif.</span></p>
                @elseif (! $dAhora || ! in_array($dAhora->estado, ['listo', 'pendiente', 'procesando'], true))
                    <p class="ct-paso"><b>1</b><span>Calcula la declaración con lo registrado en el ERP.</span></p>
                    <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                            wire:click="pedir({{ $al['anio'] }}, {{ $al['mes'] }})" wire:loading.attr="disabled" wire:target="pedir">
                        Calcular la declaración
                    </button>
                @elseif ($dAhora->estado !== 'listo')
                    <p class="ct-paso"><b>1</b><span>Calculando la declaración… esta página se actualiza sola.</span></p>
                @else
                    <p class="ct-paso"><b>1</b><span>Entra a <a href="https://srienlinea.sri.gob.ec" target="_blank" rel="noopener">SRI en línea</a>
                        con el RUC y la clave de la empresa, y abre la declaración del IVA de {{ $al['etiqueta'] }}.</span></p>
                    <p class="ct-paso"><b>2</b><span>Escribe en el formulario estas casillas. Las que no aparecen aquí se quedan en cero.</span></p>

                    @include('filament.contabilidad.parciales.casillas-104', ['guia' => $ahora['guia']])

                    <p class="ct-paso" style="margin-top:16px"><b>3</b><span>Antes de enviar, los totales del portal deben ser los de arriba. Si uno no coincide, revisa lo que escribiste.</span></p>
                    <p class="ct-paso"><b>4</b><span>Envía la declaración. El portal te entrega un «Comprobante electrónico para pago»: guárdalo, pero <strong>no es el que se sube aquí</strong>, porque no trae las casillas.</span></p>
                    <p class="ct-paso"><b>5</b><span>En SRI en línea entra a <strong>Declaraciones presentadas</strong>, descarga la declaración de {{ $al['etiqueta'] }} (el PDF de varias páginas con todas las casillas) y súbela aquí. El ERP la compara casilla por casilla con lo que calculó y el IVA queda presentado.</span></p>
                    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:10px">
                        <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                                wire:click="mountAction('subirComprobante', { id: {{ $dAhora->id }} })">
                            Subir la declaración presentada
                        </button>
                        <button type="button" class="ct-pestana" wire:click="pedir({{ $al['anio'] }}, {{ $al['mes'] }})"
                                wire:loading.attr="disabled" wire:target="pedir"
                                title="Si registraste facturas después de calcular, vuelve a calcular antes de declarar">
                            Volver a calcular
                        </button>
                        @if ($dAhora->generado_en)
                            <span class="ct-pie2" style="align-self:center">calculado el {{ $dAhora->generado_en->format('d/m/Y \a \l\a\s H:i') }}</span>
                        @endif
                    </div>
                @endif
              </div>

              @if ($ahora['debe_ats'])
                  {{-- El ATS se presenta con el 104: mismo mes, mismo lugar. --}}
                  <div class="ct-parte">
                      <p class="ct-parte-tit">ATS · anexo transaccional</p>
                      @include('filament.contabilidad.parciales.ats-pasos', ['ats' => $ahora['ats'], 'anio' => $al['anio'], 'mes' => $al['mes'], 'etiqueta' => $al['etiqueta']])
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
            <p class="ct-tit" style="margin-top:7px">Vencidos sin presentar</p>
            <p class="ct-pie2">{{ $cumplimiento['vencidos'] ? 'pasó la fecha y no hay declaración del SRI' : 'ninguno fuera de plazo' }}</p>
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
                        @if ($p['debe_ats'])
                            · ATS {{ $p['ats']?->presentado_en ? 'presentado' : ($p['ats']?->estado === 'listo' ? 'generado, falta presentar' : 'sin generar') }}
                        @endif
                    </p>
                </div>

                @php $esElDeArriba = $ahora && $ahora['alerta']['anio'] === $p['anio'] && $ahora['alerta']['mes'] === $p['mes']; @endphp

                @if ($esElDeArriba)
                    {{-- Un mes, un solo lugar: el que toca declarar se trabaja en el bloque de arriba. --}}
                    <p class="ct-fila-s" style="flex:1; text-align:right">Se declara en el bloque de arriba</p>
                @elseif ($d?->estado === 'listo' && $d->datos)
                    @php $r = $this->resumenDe($d->id); @endphp
                    {{-- Lo que generó el sistema, antes de ir al portal --}}
                    <div class="ct-resumen">
                        @foreach (array_filter([
                            ['Ventas netas', $r['ventas_netas']],
                            ['Compras netas', $r['compras_netas']],
                            $r['a_pagar'] > 0 ? ['A pagar', $r['a_pagar']] : null,
                            ['Crédito para el próximo mes', $r['arrastra']],
                        ]) as [$etiqueta, $valor])
                            <div>
                                <span class="ct-resumen-n">{{ $money($valor) }}</span>
                                <span class="ct-resumen-e">{{ $etiqueta }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div style="display:flex; align-items:center; gap:10px; flex-shrink:0; flex-wrap:wrap; justify-content:flex-end">
                    @if ($esElDeArriba)
                    @elseif ($d?->estado === 'listo' && ! $d->presentado_en)
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
                            Subir la declaración presentada
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
                        <li>Al terminar, en SRI en línea → Declaraciones presentadas, descarga la declaración del mes (la de varias páginas con las casillas, no el comprobante de pago) y súbela aquí.</li>
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

</div>
</x-filament-panels::page>
