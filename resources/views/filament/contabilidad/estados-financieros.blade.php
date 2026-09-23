<x-filament-panels::page>
@include('filament.contabilidad._estilos')
@php $money = fn ($n) => '$ ' . number_format((float) $n, 2, ',', '.'); @endphp

<div class="ct">
    @if ($sinMapear > 0)
        <div class="ct-aviso">
            <p>{{ $sinMapear }} cuenta(s) sin línea del estado: sus saldos no entran en este informe.</p>
            <a href="{{ \App\Filament\Contabilidad\Resources\PlanDeCuentasResource::getUrl() }}" class="ct-chip ct-chip-wa" style="text-decoration:none">Asignar</a>
        </div>
    @endif

    <div class="ct-tabs">
        @foreach ($estados as $e)
            <a class="ct-tab {{ $e === $estado ? 'activo' : '' }}"
               href="{{ \App\Filament\Contabilidad\Pages\EstadosFinancieros::getUrl() }}?estado={{ urlencode($e) }}&anio={{ $anio }}">{{ $e }}</a>
        @endforeach
    </div>

    <section class="ct-panel">
        <header>
            <h2 class="ct-tit">{{ $estado }} · {{ $empresa->name }}</h2>
            <span class="ct-pie2">
                <a href="{{ \App\Filament\Contabilidad\Pages\EstadosFinancieros::getUrl() }}?estado={{ urlencode($estado) }}&anio={{ $anio - 1 }}">← {{ $anio - 1 }}</a>
                &nbsp;·&nbsp;
                <a href="{{ \App\Filament\Contabilidad\Pages\EstadosFinancieros::getUrl() }}?estado={{ urlencode($estado) }}&anio={{ $anio + 1 }}">{{ $anio + 1 }} →</a>
            </span>
        </header>
        <table class="ct-tabla">
            <thead>
                <tr><th>Línea</th><th style="text-align:right">{{ $anio }}</th><th style="text-align:right">{{ $anio - 1 }}</th><th style="text-align:right">Variación</th></tr>
            </thead>
            <tbody>
            @foreach ($filas as $f)
                @php $var = $f['anterior'] != 0 ? round(($f['actual'] - $f['anterior']) / abs($f['anterior']) * 100, 1) : null; @endphp
                <tr>
                    <td data-col="Línea">{{ $f['etiqueta'] }}</td>
                    <td class="num" data-col="{{ $anio }}">{{ $money($f['actual']) }}</td>
                    <td class="num" data-col="{{ $anio - 1 }}" style="color:var(--m)">{{ $money($f['anterior']) }}</td>
                    <td class="num" data-col="Variación" style="color:{{ $var === null ? 'var(--m2)' : ($var >= 0 ? 'var(--ok)' : 'var(--da)') }}">
                        {{ $var === null ? '—' : ($var > 0 ? '+' : '') . number_format($var, 1, ',', '.') . ' %' }}
                    </td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td data-col="Total">Total</td>
                    <td class="num" data-col="{{ $anio }}">{{ $money($totalA) }}</td>
                    <td class="num" data-col="{{ $anio - 1 }}">{{ $money($totalB) }}</td>
                    <td class="num"></td>
                </tr>
            </tfoot>
        </table>
    </section>

    <p class="ct-pie2">
        Cada cuenta suma a su línea por el mapeo del plan de cuentas. El comparativo del ejercicio anterior
        es parte del estado bajo NIIF, no un extra.
    </p>
</div>
</x-filament-panels::page>
