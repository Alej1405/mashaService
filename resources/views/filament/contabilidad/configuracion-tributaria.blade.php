<x-filament-panels::page>
@include('filament.contabilidad._estilos')
@php
    $money = fn ($n) => '$ ' . number_format((float) $n, 2, ',', '.');
    $pct = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',') . ' %';
@endphp

<div class="ct">
    <section class="ct-panel">
        <header>
            <h2 class="ct-tit">Tarifas de IVA</h2>
            <span class="ct-pie2">se aplican por la fecha del comprobante</span>
        </header>
        <table class="ct-tabla">
            <thead><tr><th>Tarifa</th><th>Aplica a</th><th>Desde</th><th>Hasta</th><th>Estado</th></tr></thead>
            <tbody>
            @foreach ($tarifas as $t)
                <tr>
                    <td data-col="Tarifa"><strong>{{ $pct($t->porcentaje) }}</strong></td>
                    <td data-col="Aplica a">{{ $t->descripcion }}</td>
                    <td data-col="Desde">{{ $t->vigente_desde?->format('d/m/Y') }}</td>
                    <td data-col="Hasta">{{ $t->vigente_hasta?->format('d/m/Y') ?? '—' }}</td>
                    <td data-col="Estado">
                        <span class="ct-chip ct-chip-{{ $t->vigente ? 'ok' : 'n' }}">{{ $t->vigente ? 'Vigente' : 'Histórica' }}</span>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>

    @foreach ($retenciones as $tipo => $lista)
        <section class="ct-panel">
            <header>
                <h2 class="ct-tit">Retención de {{ $tipo === 'renta' ? 'impuesto a la renta' : 'IVA' }}</h2>
                <span class="ct-pie2">
                    {{ $tipo === 'renta' ? 'sobre la base imponible' : 'sobre el IVA de la factura, no sobre la base' }}
                </span>
            </header>
            <table class="ct-tabla">
                <thead><tr><th>Concepto</th><th style="text-align:right">Porcentaje</th><th>Desde</th><th>Código SRI</th><th>Resolución</th></tr></thead>
                <tbody>
                @foreach ($lista as $p)
                    <tr>
                        <td data-col="Concepto">{{ $p->concepto }}</td>
                        <td class="num" data-col="Porcentaje"><strong>{{ $pct($p->porcentaje) }}</strong></td>
                        <td data-col="Desde">{{ $p->vigente_desde?->format('d/m/Y') }}</td>
                        <td data-col="Código SRI">
                            @if ($p->codigo_sri)
                                {{ $p->codigo_sri }}
                            @else
                                <span class="ct-chip ct-chip-wa">falta la ficha del SRI</span>
                            @endif
                        </td>
                        <td data-col="Resolución" style="color:var(--m2)">{{ $p->resolucion }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>
    @endforeach

    <section class="ct-panel">
        <header><h2 class="ct-tit">La compañía ante la Superintendencia</h2></header>
        @foreach ([
            [$empresa->tipo_compania ?: 'Tipo de compañía sin registrar', 'sale del documento del RUC',
             $empresa->tipo_compania ? 'Registrado' : 'Falta', $empresa->tipo_compania ? 'ok' : 'da'],
            [$clasificacion['marco'], 'activos ' . $money($clasificacion['activos']) . ' · ventas ' . $money($clasificacion['ventas']),
             'Calculado', 'n'],
            [$empresa->agente_retencion ? 'Es agente de retención' : 'No es agente de retención',
             'define si cada compra genera comprobante de retención',
             $empresa->agente_retencion ? 'Activo' : 'Inactivo', $empresa->agente_retencion ? 'ok' : 'n'],
            [$clasificacion['audita'] ? 'Auditoría externa obligatoria' : 'Auditoría externa no obligatoria',
             'umbral de 1.366 SBU = ' . $money($clasificacion['umbral_auditoria']),
             $clasificacion['audita'] ? 'Obligatoria' : 'No aplica', $clasificacion['audita'] ? 'wa' : 'n'],
            [$societario['socios'] . ' socios · ' . $societario['administradores'] . ' administradores',
             'capital suscrito ' . $money($societario['capital']),
             $societario['socios'] ? 'Registrados' : 'Sin registrar', $societario['socios'] ? 'ok' : 'da'],
        ] as [$titulo, $sub, $chip, $tono])
            <div class="ct-fila">
                <div><p class="ct-fila-t">{{ $titulo }}</p><p class="ct-fila-s">{{ $sub }}</p></div>
                <span class="ct-chip ct-chip-{{ $tono }}">{{ $chip }}</span>
            </div>
        @endforeach
    </section>

    <p class="ct-pie2">
        El umbral de auditoría se calcula con el salario básico del año, no con una cifra fija: cambia cada enero.
        Los códigos del SRI se llenan cuando llegue su ficha técnica; no se inventan.
    </p>
</div>
</x-filament-panels::page>
