<x-filament-panels::page>
@include('filament.contabilidad._estilos')
@php
    $money = fn ($n) => number_format((float) $n, 2, ',', '.');
@endphp

<div class="ct">
    {{-- Elegir qué estado y de qué año: es lo primero que se hace aquí --}}
    <div class="ct-barra">
        @foreach ($estados as $clave => $etiqueta)
            <button type="button" wire:click="cambiarEstado('{{ $clave }}')"
                    class="ct-pestana {{ $estado === $clave ? 'es-activa' : '' }}">
                {{ $etiqueta }}
            </button>
        @endforeach

        <span style="flex:1"></span>

        @foreach ($anios as $a)
            <button type="button" wire:click="cambiarAnio({{ $a }})"
                    class="ct-pestana {{ $anio === $a ? 'es-activa' : '' }}">{{ $a }}</button>
        @endforeach
    </div>

    @if ($matriz)
        {{-- Este estado es una matriz: conceptos en filas, movimientos en columnas --}}
        <section class="ct-panel">
            <header>
                <h2 class="ct-tit">{{ $estados[$estado] }} · {{ $anio }}</h2>
                <span class="ct-pie2">
                    {{ count($matriz['conceptos']) }} conceptos × {{ count($matriz['columnas']) }} movimientos
                </span>
            </header>

            <div class="ct-matriz-envoltura">
                <table class="ct-matriz">
                    <thead>
                        <tr>
                            <th class="ct-matriz-fija">Concepto patrimonial</th>
                            @foreach ($matriz['columnas'] as $col)
                                <th class="{{ in_array($col['codigo'], $matriz['derivadas'], true) ? '' : 'es-vacia' }}">
                                    {{ \Illuminate\Support\Str::limit($col['nombre'], 34) }}
                                    <span>{{ $col['codigo'] }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($matriz['conceptos'] as $c)
                            <tr>
                                <td class="ct-matriz-fija">
                                    {{ \Illuminate\Support\Str::limit($c['nombre'], 44) }}
                                    <span>{{ $c['codigo'] }}</span>
                                </td>
                                @foreach ($matriz['columnas'] as $col)
                                    @php $valor = $matriz['celdas'][$col['codigo']][$c['codigo']] ?? 0; @endphp
                                    <td class="{{ $valor != 0 ? 'es-valor' : '' }}">
                                        {{ number_format($valor, 2, ',', '.') }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ct-fila" style="background:var(--s2)">
                <span class="ct-fila-s">
                    Las columnas en gris no se deducen del libro diario: aumentos de capital,
                    dividendos y transferencias entre cuentas patrimoniales son decisiones de junta.
                    Se llenan a mano antes de presentar.
                </span>
            </div>
        </section>
    @else
    <section class="ct-panel">
        <header>
            <h2 class="ct-tit">{{ $estados[$estado] }} · {{ $anio }}</h2>
            <div style="display:flex; gap:10px; align-items:center">
                <span class="ct-pie2">{{ $conValor }} de {{ $total }} líneas con saldo</span>
                <button type="button" class="ct-chip ct-chip-n" style="border:0;cursor:pointer"
                        wire:click="alternarVacias">
                    {{ $verTodas ? 'Ocultar las líneas en cero' : 'Ver todas las líneas' }}
                </button>
            </div>
        </header>

        @forelse ($lineas as $l)
            @php
                // El nivel del catálogo marca la jerarquía: 1 es el total, 7 el detalle.
                $sangria = max(0, ($l['nivel'] - 1)) * 9;
                $fuerte = $l['nivel'] <= 3;
            @endphp
            <div class="ct-fila" style="padding-top:9px; padding-bottom:9px">
                <div style="min-width:0; padding-left:{{ $sangria }}px">
                    <p class="{{ $fuerte ? 'ct-fila-t' : 'ct-fila-s' }}" style="{{ $fuerte ? '' : 'color:var(--t2)' }}">
                        {{ $l['nombre'] }}
                    </p>
                    <p class="ct-fila-s" style="font-size:11px">{{ $l['codigo'] }}</p>
                </div>
                <div style="display:flex; gap:26px; flex-shrink:0; text-align:right">
                    <span class="ct-cantidad" style="{{ $fuerte ? '' : 'font-weight:500' }}">
                        {{ $money($l['actual']) }}
                    </span>
                    <span class="ct-cantidad" style="color:var(--m2); font-weight:500; min-width:88px">
                        {{ $money($l['anterior']) }}
                    </span>
                </div>
            </div>
        @empty
            <p class="ct-vacio">
                Este estado no tiene ninguna línea con saldo en {{ $anio }}.
                Si esperabas cifras, revisa que el ejercicio tenga asientos confirmados.
            </p>
        @endforelse

        <div class="ct-fila" style="background:var(--s2)">
            <span class="ct-fila-s">Columna izquierda: {{ $anio }} · derecha: {{ $anio - 1 }}</span>
            <span class="ct-fila-s">
                La estructura es la del catálogo de la Superintendencia, la misma del archivo que se sube
            </span>
        </div>
    </section>
    @endif
</div>
</x-filament-panels::page>
