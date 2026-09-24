<x-filament-panels::page>
@include('filament.contabilidad._estilos')
@php
    $money = fn ($n) => '$ ' . number_format((float) $n, 2, ',', '.');
@endphp

<div class="ct">
    {{-- Lo primero: el veredicto, que es a lo que se viene --}}
    <div class="ct-rej-4">
        <div class="ct-card">
            <div class="ct-card-cab">
                <span class="ct-tit">Ante el SRI</span>
                <span class="ct-chip ct-chip-{{ $cumplimiento['vencidos'] === 0 ? 'ok' : 'da' }}">
                    {{ $cumplimiento['vencidos'] === 0 ? 'Al día' : 'Con atrasos' }}
                </span>
            </div>
            <p class="ct-val">{{ $cumplimiento['presentados'] }}/{{ $cumplimiento['total'] }}</p>
            <p class="ct-pie2">períodos presentados desde {{ $cumplimiento['desde'] ?? '—' }}</p>
        </div>

        <div class="ct-card">
            <span class="ct-tit">Facturación declarada</span>
            <p class="ct-val">{{ $money($ventas['total']) }}</p>
            <p class="ct-pie2">
                últimos 12 meses · {{ $ventas['con_movimiento'] }} con ventas
            </p>
        </div>

        <div class="ct-card">
            <span class="ct-tit">Crédito tributario</span>
            <p class="ct-val">{{ $money($tributaria['credito_acumulado']) }}</p>
            <p class="ct-pie2">
                {{ $tributaria['credito_acumulado'] > 0 ? 'a favor, recuperable contra el IVA de ventas' : 'sin saldo a favor' }}
            </p>
        </div>

        <div class="ct-card">
            <span class="ct-tit">Endeudamiento</span>
            <p class="ct-val">{{ $financiera['endeudamiento'] !== null ? $financiera['endeudamiento'] . ' %' : '—' }}</p>
            <p class="ct-pie2">del activo financiado con deuda</p>
        </div>
    </div>

    {{-- El porqué de cada número, en el lenguaje de quien decide --}}
    <section class="ct-panel">
        <header>
            <h2 class="ct-tit">Lo que vería una entidad financiera</h2>
            <span class="ct-pie2">{{ count($señales) }} señales</span>
        </header>
        @foreach ($señales as $s)
            <div class="ct-fila">
                <div style="min-width:0">
                    <p class="ct-fila-t">{{ $s['titulo'] }}</p>
                    <p class="ct-fila-s">{{ $s['detalle'] }}</p>
                </div>
                <span class="ct-chip ct-chip-{{ $s['tono'] }}">
                    {{ ['ok' => 'A favor', 'wa' => 'A revisar', 'da' => 'En contra', 'n' => 'Dato'][$s['tono']] }}
                </span>
            </div>
        @endforeach
    </section>

    <div class="ct-cols">
        {{-- Lo que consta en el SRI --}}
        <section class="ct-panel">
            <header>
                <h2 class="ct-tit">Declaraciones presentadas</h2>
                <span class="ct-pie2">
                    {{ $tributaria['declaraciones'] }} en total
                    @if ($tributaria['cargadas']) · {{ $tributaria['cargadas'] }} cargadas a mano @endif
                </span>
            </header>
            @forelse ($ventas['meses'] as $m)
                <div class="ct-fila">
                    <div>
                        <p class="ct-fila-t">{{ $m['periodo'] }}</p>
                        <p class="ct-fila-s">ventas {{ $money($m['ventas']) }} · compras {{ $money($m['compras']) }}</p>
                    </div>
                    <span class="ct-cantidad">{{ $money($m['pagado']) }}</span>
                </div>
            @empty
                <p class="ct-vacio">
                    Todavía no consta ninguna declaración presentada.
                    Si la empresa ya declaraba antes del ERP, cárgalas con el botón de arriba:
                    es lo que da historial ante un banco.
                </p>
            @endforelse
        </section>

        {{-- Lo que consta en la Superintendencia --}}
        <section class="ct-panel">
            <header>
                <h2 class="ct-tit">Estado financiero · {{ now()->year }}</h2>
                <span class="ct-pie2">{{ $financiera['clasificacion']['marco'] ?? '' }}</span>
            </header>
            @foreach ([
                ['Activo', $financiera['activo'], 'lo que tiene'],
                ['Pasivo', $financiera['pasivo'], 'lo que debe'],
                ['Patrimonio', $financiera['patrimonio'], 'lo que es suyo'],
                ['Resultado del ejercicio', $financiera['resultado'], 'ingresos menos costos y gastos'],
            ] as [$etiqueta, $valor, $pie])
                <div class="ct-fila">
                    <div>
                        <p class="ct-fila-t">{{ $etiqueta }}</p>
                        <p class="ct-fila-s">{{ $pie }}</p>
                    </div>
                    <span class="ct-cantidad {{ $valor < 0 ? '' : 'op-entra' }}">{{ $money($valor) }}</span>
                </div>
            @endforeach
            <div class="ct-fila">
                <div>
                    <p class="ct-fila-t">Liquidez</p>
                    <p class="ct-fila-s">activo sobre pasivo · aproximada, sin separar corriente</p>
                </div>
                <span class="ct-cantidad">{{ $financiera['liquidez'] !== null ? number_format($financiera['liquidez'], 2, ',', '.') : '—' }}</span>
            </div>
        </section>
    </div>
</div>
</x-filament-panels::page>
