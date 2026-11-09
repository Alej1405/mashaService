<x-filament-panels::page>
@include('filament.contabilidad._estilos')
@php
    $money = fn ($n) => '$ ' . number_format((float) $n, 2, ',', '.');
    $nombres = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
                7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
@endphp

<div class="ct">
    @if ($descuadrados > 0)
        <div class="ct-aviso">
            <p>{{ $descuadrados }} asiento(s) descuadrado(s) en {{ $anio }}. El ejercicio no se puede cerrar hasta corregirlos.</p>
            <a href="{{ \App\Filament\Contabilidad\Resources\AsientoResource::getUrl() }}" class="ct-chip ct-chip-wa" style="text-decoration:none">Ver asientos</a>
        </div>
    @endif

    <div class="ct-rej-4">
        @foreach ([['Activo', $saldos['activo']], ['Pasivo', $saldos['pasivo']], ['Patrimonio', $saldos['patrimonio']], ['Resultado', $saldos['resultado']]] as [$et, $v])
            <div class="ct-card">
                <p class="ct-val">{{ $money($v) }}</p>
                <p class="ct-tit" style="margin-top:7px">{{ $et }}</p>
                <p class="ct-pie2">al cierre de {{ $anio }}</p>
            </div>
        @endforeach
    </div>

    <section class="ct-panel">
        <header style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <h2 class="ct-tit">Periodos del ejercicio {{ $anio }}</h2>
            <select wire:model.live="anio" class="ct-select" aria-label="Año">
                @foreach ($anios as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach
            </select>
        </header>
        <table class="ct-tabla">
            <thead><tr><th>Mes</th><th>Asientos</th><th style="text-align:right">Debe</th><th style="text-align:right">Haber</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @for ($n = $primerMes; $n <= $ultimoMes; $n++)
                @php
                    $m = $meses[$n] ?? null;
                    $p = $periodos[$n] ?? null;
                    $antes = $inicio && \Illuminate\Support\Carbon::create($anio, $n, 1)->lessThan($inicio);
                @endphp
                <tr>
                    <td data-col="Mes">{{ $nombres[$n] }}</td>
                    <td data-col="Asientos">{{ $m->asientos ?? 0 }}</td>
                    <td class="num" data-col="Debe">{{ $money($m->debe ?? 0) }}</td>
                    <td class="num" data-col="Haber">{{ $money($m->haber ?? 0) }}</td>
                    <td data-col="Estado">
                        <span class="ct-chip ct-chip-{{ $p?->cerrado_en ? 'ok' : 'wa' }}">
                            {{ $p?->cerrado_en ? ($p->motivo === 'cuadre' ? 'Cerrado en el cuadre' : 'Cerrado') : 'Abierto' }}
                        </span>
                    </td>
                    <td data-col="" style="text-align:right">
                        @if (! $p?->cerrado_en && $antes)
                            <button type="button" class="ct-btn-descarga" wire:click="mountAction('cerrarMes', { mes: {{ $n }} })">Cerrar mes</button>
                        @elseif ($p?->cerrado_en && $p->motivo === 'cuadre')
                            <button type="button" class="ct-chip ct-chip-wa" style="cursor:pointer;border:0" wire:click="mountAction('reabrirMes', { mes: {{ $n }} })">Reabrir</button>
                        @elseif (! $p?->cerrado_en)
                            <span class="ct-fila-s">se cierra con su 104</span>
                        @endif
                    </td>
                </tr>
            @endfor
            </tbody>
        </table>
    </section>

    <div class="ct-cols">
        <section class="ct-panel">
            <header><h2 class="ct-tit">Qué hace el cierre anual</h2></header>
            @foreach ([
                ['1. Comprueba que todo cuadre', 'ningún asiento descuadrado ni sin confirmar'],
                ['2. Calcula el resultado', 'ingresos menos costos y gastos del ejercicio'],
                ['3. Lo lleva al patrimonio', 'queda escrito en el ejercicio cerrado'],
                ['4. Bloquea el año', 'no se admiten asientos con esa fecha'],
            ] as [$t, $s])
                <div class="ct-fila">
                    <div><p class="ct-fila-t">{{ $t }}</p><p class="ct-fila-s">{{ $s }}</p></div>
                </div>
            @endforeach
        </section>

        <section class="ct-panel">
            <header><h2 class="ct-tit">Ejercicios</h2></header>
            @forelse ($ejercicios as $e)
                <div class="ct-fila">
                    <div>
                        <p class="ct-fila-t">Ejercicio {{ $e->anio }}</p>
                        <p class="ct-fila-s">
                            {{ $e->cerrado_en ? 'cerrado el ' . $e->cerrado_en->format('d/m/Y') . ' · resultado ' . $money($e->resultado) : 'sin cerrar' }}
                        </p>
                    </div>
                    <span class="ct-chip ct-chip-{{ $e->cerrado_en ? 'ok' : 'wa' }}">{{ $e->cerrado_en ? 'Bloqueado' : 'Abierto' }}</span>
                </div>
            @empty
                <div class="ct-fila"><div><p class="ct-fila-t">Ningún ejercicio cerrado todavía</p>
                    <p class="ct-fila-s">el primero que se cierre quedará bloqueado</p></div></div>
            @endforelse
        </section>
    </div>
</div>
</x-filament-panels::page>
