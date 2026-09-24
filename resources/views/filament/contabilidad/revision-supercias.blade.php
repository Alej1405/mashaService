<x-filament-panels::page>
@include('filament.contabilidad._estilos')

<div class="ct">
    <div class="ct-rej">
        <div class="ct-card">
            <p class="ct-val">{{ $total }}</p>
            <p class="ct-tit" style="margin-top:7px">Cuentas en el balance</p>
            <p class="ct-pie2">
                {{ $sinMapear === 0
                    ? 'todas suman a una línea del catálogo'
                    : $sinMapear . ' sin línea: avísame, no debería pasar' }}
            </p>
        </div>
        <div class="ct-card">
            <div class="ct-card-cab">
                <span class="ct-tit">Por confirmar</span>
                <span class="ct-chip ct-chip-{{ $porRevisar > 0 ? 'wa' : 'ok' }}">
                    {{ $porRevisar > 0 ? 'Pendiente' : 'Al día' }}
                </span>
            </div>
            <p class="ct-val">{{ $porRevisar }}</p>
            <p class="ct-pie2">
                ya suman al balance con la línea que propuso el sistema;
                confirmarlas es decir que esa es la correcta
            </p>
        </div>
        <div class="ct-card">
            <span class="ct-tit">Qué significa esto</span>
            <p class="ct-pie" style="margin-top:8px">
                <strong>Ninguna cuenta se queda fuera del balance.</strong>
                El sistema le pone su línea a cada una al crearla. Lo que ves abajo son
                aquellas en las que no estuvo seguro: el balance ya las incluye, y al
                confirmarlas pasan a ser decisión tuya.
            </p>
        </div>
    </div>

    <section class="ct-panel">
        <header>
            <h2 class="ct-tit">
                {{ $verTodas ? 'Todas las cuentas' : 'Cuentas que conviene mirar' }}
            </h2>
            <div style="display:flex; gap:10px; align-items:center">
                <button type="button" class="ct-chip ct-chip-n" style="border:0;cursor:pointer"
                        wire:click="alternar">
                    {{ $verTodas ? 'Ver solo las dudosas' : 'Ver todas' }}
                </button>
                @if ($cuentas->isNotEmpty())
                    <button type="button" class="ct-btn-descarga" style="border:0;cursor:pointer"
                            wire:click="confirmar">
                        Confirmar las {{ $cuentas->count() }}
                    </button>
                @endif
            </div>
        </header>

        @forelse ($cuentas as $c)
            @php
                $tono = $c['confianza'] >= 75 ? 'ok' : ($c['confianza'] >= 50 ? 'wa' : 'da');
                $id = $c['cuenta']->id;
            @endphp
            <div class="ct-fila" style="align-items:flex-start; flex-wrap:wrap; gap:10px">
                <div style="min-width:220px; flex:1">
                    <p class="ct-fila-t">{{ $c['cuenta']->code }} · {{ $c['cuenta']->name }}</p>
                    <p class="ct-fila-s">
                        propuesta: {{ $c['linea'] ?? 'ninguna' }}
                        <span class="ct-chip ct-chip-{{ $tono }}" style="margin-left:6px">{{ $c['confianza'] }} %</span>
                    </p>
                </div>

                <div style="display:flex; gap:8px; align-items:center; flex:1 1 380px; min-width:0">
                    <select wire:model="eleccion.{{ $id }}"
                            style="flex:1; min-width:0; padding:8px 10px; border:1px solid var(--b);
                                   border-radius:10px; font-size:13px; background:var(--s); color:var(--t)">
                        <option value="">— sin línea —</option>
                        {{-- Primero lo que el sistema encontró más parecido --}}
                        <optgroup label="Las más parecidas">
                            @foreach ($c['alternativas'] as $a)
                                <option value="{{ $a['codigo'] }}">
                                    {{ $a['codigo'] }} · {{ \Illuminate\Support\Str::limit($a['nombre'], 60) }} ({{ $a['confianza'] }} %)
                                </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Todo el catálogo">
                            @foreach ($catalogo as $codigo => $etiqueta)
                                <option value="{{ $codigo }}">{{ \Illuminate\Support\Str::limit($etiqueta, 70) }}</option>
                            @endforeach
                        </optgroup>
                    </select>

                    <button type="button" class="ct-chip ct-chip-ok" style="border:0;cursor:pointer;flex-shrink:0"
                            wire:click="confirmarUna({{ $id }})">
                        Confirmar
                    </button>
                </div>
            </div>
        @empty
            <p class="ct-vacio">
                Todas las cuentas tienen su línea del estado y están confirmadas.
                El balance para la Superintendencia sale completo.
            </p>
        @endforelse
    </section>

    <section class="ct-panel">
        <header><h2 class="ct-tit">De dónde sale cada propuesta</h2></header>
        @foreach ([
            ['Por el nombre de la cuenta', 'se compara con las líneas del catálogo, palabra por palabra', 'ok'],
            ['Por el grupo al que pertenece', 'cuando el nombre no dice nada, manda su cuenta padre', 'wa'],
            ['Por la estructura del código', '1.1 es activo corriente, 6 son gastos: nunca falla, pero es gruesa', 'da'],
        ] as [$titulo, $detalle, $tono])
            <div class="ct-fila">
                <div><p class="ct-fila-t">{{ $titulo }}</p><p class="ct-fila-s">{{ $detalle }}</p></div>
                <span class="ct-chip ct-chip-{{ $tono }}">
                    {{ $tono === 'ok' ? 'fiable' : ($tono === 'wa' ? 'revisar' : 'revisar siempre') }}
                </span>
            </div>
        @endforeach
    </section>
</div>
</x-filament-panels::page>
