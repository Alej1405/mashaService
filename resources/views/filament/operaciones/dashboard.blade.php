<x-filament-panels::page>
@php
    $fmt = fn ($n, $d = 2) => rtrim(rtrim(number_format((float) $n, $d, ',', '.'), '0'), ',');
    $registrar = \App\Filament\Operaciones\Pages\RegistrarMovimiento::getUrl();
    $productos = \App\Filament\Operaciones\Resources\InventarioResource::getUrl();
@endphp

{{-- Estilos propios, como en la ficha del ítem: el panel no carga el tema del
     panel App y el CSS base de Filament no trae todas las utilidades. --}}
@include('filament.operaciones._estilos')

<div class="op">
    {{-- Primero la acción: a esto se entra a trabajar, no a mirar --}}
    <div class="op-barra" x-data="escanerDeBodega()">
        <form action="{{ $productos }}" method="GET" class="op-buscar">
            <label>
                <span class="sr-only">Buscar producto</span>
                <input type="search" name="tableSearch" autocomplete="off"
                       placeholder="Busca por nombre o código">
            </label>
        </form>

        <p class="op-ayuda-escaneo" x-show="! soportado" x-cloak>
            Para escanear, apunta la cámara del teléfono al QR de la gaveta: abre la ficha sola.
        </p>

        {{-- El lector usa el del propio navegador. Donde no existe (iPhone), se
             explica que la cámara del teléfono ya abre la ficha con el QR. --}}
        <button type="button" class="op-btn" x-on:click="abrir" x-show="soportado" x-cloak>
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3.75 4.875A1.125 1.125 0 0 1 4.875 3.75h2.25M20.25 4.875A1.125 1.125 0 0 0 19.125 3.75h-2.25M3.75 19.125c0 .621.504 1.125 1.125 1.125h2.25M20.25 19.125c0 .621-.504 1.125-1.125 1.125h-2.25M3 12h18"/>
            </svg>
            Escanear
        </button>
        <a class="op-btn op-btn-principal" href="{{ $registrar }}?tipo=entrada">Entrada</a>
        <a class="op-btn" href="{{ $registrar }}?tipo=salida">Salida</a>
        <a class="op-btn" href="{{ $registrar }}?tipo=entrada&amp;motivo=ajuste_sobrante">Conteo</a>

        <div class="op-visor" x-show="abierto" x-cloak>
            <p x-text="mensaje"></p>
            <video x-ref="video" x-show="soportado" autoplay muted playsinline></video>
            <button type="button" class="op-btn" x-on:click="cerrar">Cerrar</button>
        </div>
    </div>

    <div class="op-cols">
        {{-- Lo que está roto --}}
        <section class="op-panel">
            <header>
                <h2>Requieren atención</h2>
                <span class="op-chip">{{ $bajoMinimo->count() }}</span>
            </header>
            @forelse($bajoMinimo as $item)
                @php $ubic = $item->stocks->firstWhere('cantidad', '>', 0)?->ubicacion; @endphp
                <div class="op-fila">
                    <div style="min-width:0">
                        <p class="op-fila-titulo">{{ $item->nombre }}</p>
                        <p class="op-fila-sub">
                            mínimo: {{ $fmt($item->stock_minimo) }}
                            @if($ubic) · {{ $ubic->codigo_ubicacion }} @endif
                        </p>
                    </div>
                    <span class="op-chip">{{ $fmt($item->stock_actual) }}</span>
                </div>
            @empty
                <p class="op-vacio">Todo sobre el mínimo. Nada que reponer hoy.</p>
            @endforelse
        </section>

        {{-- Lo que pasó --}}
        <section class="op-panel">
            <header>
                <h2>Movimientos de hoy</h2>
                <span class="op-hint">{{ $entradasHoy }} entradas · {{ $salidasHoy }} salidas</span>
            </header>
            @forelse($movimientosHoy as $m)
                <div class="op-fila">
                    <div style="min-width:0">
                        <p class="op-fila-titulo">{{ $m->inventoryItem?->nombre ?? 'ítem eliminado' }}</p>
                        <p class="op-fila-sub">
                            {{ ucfirst(str_replace('_', ' ', $m->motivo ?? $m->type)) }} · {{ $m->created_at?->format('H:i') }}
                        </p>
                    </div>
                    <span class="op-cantidad {{ $m->type === 'entrada' ? 'op-entra' : '' }}">
                        {{ $m->type === 'entrada' ? '+' : '−' }}{{ $fmt($m->quantity) }}
                    </span>
                </div>
            @empty
                <p class="op-vacio">Sin movimientos hoy.</p>
            @endforelse
        </section>
    </div>

    {{-- El contexto va al final: informa, no manda --}}
    <div class="op-metricas">
        @foreach ([
            ['valor' => $items->count(), 'etiqueta' => 'Ítems en bodega', 'sub' => 'activos'],
            ['valor' => $totalHoy, 'etiqueta' => 'Movimientos hoy', 'sub' => $entradasHoy . ' entradas · ' . $salidasHoy . ' salidas'],
            ['valor' => '$ ' . number_format($valorTotal, 2, ',', '.'), 'etiqueta' => 'Valor del inventario', 'sub' => 'a costo promedio'],
            ['valor' => $sinUbicar, 'etiqueta' => 'Sin ubicación', 'sub' => $sinUbicar ? 'no aparecen en la gaveta' : 'todo ubicado'],
        ] as $m)
            <div class="op-card">
                <p class="op-val">{{ $m['valor'] }}</p>
                <p class="op-lbl">{{ $m['etiqueta'] }}</p>
                <p class="op-hint">{{ $m['sub'] }}</p>
            </div>
        @endforeach
    </div>
</div>

<script>
    function escanerDeBodega() {
        return {
            abierto: false,
            soportado: 'BarcodeDetector' in window,
            mensaje: '',
            flujo: null,

            async abrir() {
                this.abierto = true;

                if (! this.soportado) {
                    this.abierto = false;

                    return;
                }

                this.mensaje = 'Apunta al código de la gaveta';

                try {
                    this.flujo = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                    this.$refs.video.srcObject = this.flujo;

                    const lector = new BarcodeDetector({ formats: ['qr_code', 'code_128', 'ean_13', 'code_39'] });

                    const buscar = async () => {
                        if (! this.abierto) return;
                        try {
                            const codigos = await lector.detect(this.$refs.video);
                            if (codigos.length) return this.usar(codigos[0].rawValue);
                        } catch (e) {}
                        requestAnimationFrame(buscar);
                    };
                    requestAnimationFrame(buscar);
                } catch (e) {
                    this.mensaje = 'No se pudo abrir la cámara: ' + e.message;
                }
            },

            usar(valor) {
                this.cerrar();
                // El QR de la gaveta lleva una URL y se abre tal cual; un código
                // de barras es texto y se busca en el inventario.
                window.location = valor.startsWith('http')
                    ? valor
                    : '{{ $productos }}?tableSearch=' + encodeURIComponent(valor);
            },

            cerrar() {
                this.abierto = false;
                this.flujo?.getTracks().forEach((t) => t.stop());
                this.flujo = null;
            },
        }
    }
</script>
</x-filament-panels::page>
