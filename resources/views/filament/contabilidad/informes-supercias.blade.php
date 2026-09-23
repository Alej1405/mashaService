<x-filament-panels::page>
@include('filament.contabilidad._estilos')

<div class="ct">
    @if ($sinCodigo > 0)
        <div class="ct-aviso">
            <p>
                {{ $sinCodigo }} cuenta(s) del plan no apuntan al catálogo de la Superintendencia:
                sus saldos saldrían en cero en el archivo.
            </p>
            <a href="{{ \App\Filament\Contabilidad\Resources\PlanDeCuentasResource::getUrl() }}"
               class="ct-chip ct-chip-wa" style="text-decoration:none">Asignar códigos</a>
        </div>
    @endif

    <div class="ct-tabs">
        @foreach ([now()->year, now()->year - 1, now()->year - 2] as $a)
            <a class="ct-tab {{ $a === $anio ? 'activo' : '' }}"
               href="{{ \App\Filament\Contabilidad\Pages\InformesSupercias::getUrl() }}?anio={{ $a }}">Ejercicio {{ $a }}</a>
        @endforeach
    </div>

    <section class="ct-panel">
        <header>
            <h2 class="ct-tit">Archivos del ejercicio {{ $anio }}</h2>
            <span class="ct-pie2">formato oficial · código y valor por línea</span>
        </header>
        <table class="ct-tabla">
            <thead><tr><th>Estado</th><th>Archivo</th><th style="text-align:right">Líneas</th><th style="text-align:right">Con valor</th><th></th></tr></thead>
            <tbody>
            @foreach ($resumen as $estado => $r)
                <tr>
                    <td data-col="Estado"><strong>{{ $r['etiqueta'] }}</strong></td>
                    <td data-col="Archivo" style="font-family:ui-monospace,monospace;font-size:13px">{{ $anio }}_{{ $r['archivo'] }}</td>
                    <td class="num" data-col="Líneas">{{ $r['lineas'] }}</td>
                    <td class="num" data-col="Con valor">
                        <span class="ct-chip ct-chip-{{ $r['conValor'] ? 'ok' : 'wa' }}">{{ $r['conValor'] }}</span>
                    </td>
                    <td data-col="" style="text-align:right">
                        <button type="button" wire:click="descargar('{{ $estado }}')" class="ct-chip ct-chip-n" style="border:0;cursor:pointer">
                            Descargar .txt
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>

    <section class="ct-panel">
        <header><h2 class="ct-tit">Así sale el archivo · primeras líneas</h2></header>
        <div style="padding:16px 18px;font-family:ui-monospace,SFMono-Regular,monospace;font-size:13px;color:#334155;line-height:1.7">
            @foreach ($muestra as $linea)
                <div>{{ $linea }}</div>
            @endforeach
            <div style="color:#94a3b8">…</div>
        </div>
    </section>

    <p class="ct-pie2">
        El archivo lleva el código del catálogo de la Superintendencia, no el del plan de cuentas de la empresa.
        Los totales se consolidan solos: lo que suma una cuenta hija sube a su padre.
    </p>
</div>
</x-filament-panels::page>
