<x-filament-panels::page>
@php
    $fmt = fn ($n, $d = 2) => rtrim(rtrim(number_format((float) $n, $d, ',', '.'), '0'), ',');
    $registrar = \App\Filament\Operaciones\Pages\RegistrarMovimiento::getUrl();
    $productos = \App\Filament\Operaciones\Resources\InventarioResource::getUrl();
@endphp

{{-- Estilos propios, como en la ficha del ítem: el panel no carga el tema del
     panel App y el CSS base de Filament no trae todas las utilidades. --}}
<style>
    .op { --op-surface:#fff; --op-border:#e2e8f0; --op-border-soft:#f1f5f9;
          --op-text:#0f172a; --op-muted:#64748b; --op-muted-2:#94a3b8;
          --op-accent:#4f46e5; --op-warn:#b45309; --op-warn-soft:#fffbeb;
          --op-ok:#047857; --op-r:14px; display:flex; flex-direction:column; gap:18px; }

    .op-barra { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
    .op-buscar { flex:1 1 320px; min-width:0; }
    .op-buscar input { width:100%; box-sizing:border-box; padding:11px 16px; font-size:14px;
                       border:1px solid var(--op-border); border-radius:12px; background:var(--op-surface);
                       color:var(--op-text); }
    .op-buscar input:focus { outline:none; border-color:var(--op-accent); box-shadow:0 0 0 3px #4f46e51f; }
    .op-btn { display:inline-flex; align-items:center; gap:7px; white-space:nowrap; flex-shrink:0;
              padding:11px 18px; font-size:14px; font-weight:700; border-radius:12px; cursor:pointer;
              border:1px solid var(--op-border); background:var(--op-surface); color:#334155;
              text-decoration:none; transition:transform .12s ease; }
    .op-btn:active { transform:scale(.98); }
    .op-btn-principal { background:var(--op-accent); border-color:var(--op-accent); color:#fff; }
    .op-btn svg { width:16px; height:16px; }

    .op-cols { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .op-panel { background:var(--op-surface); border:1px solid var(--op-border);
                border-radius:var(--op-r); overflow:hidden; }
    .op-panel header { display:flex; align-items:center; justify-content:space-between;
                       gap:12px; padding:14px 18px; border-bottom:1px solid var(--op-border-soft); }
    .op-panel h2 { margin:0; font-size:11px; font-weight:700; letter-spacing:.9px;
                   text-transform:uppercase; color:var(--op-muted); }
    .op-fila { display:flex; align-items:center; justify-content:space-between; gap:12px;
               padding:12px 18px; border-bottom:1px solid var(--op-border-soft); }
    .op-fila:last-child { border-bottom:0; }
    .op-fila-titulo { font-size:14px; font-weight:700; color:var(--op-text);
                      overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .op-fila-sub { font-size:12px; color:var(--op-muted); margin-top:2px; }
    .op-vacio { padding:30px 18px; text-align:center; font-size:14px; color:var(--op-muted); }
    .op-chip { flex-shrink:0; padding:3px 10px; border-radius:999px; font-size:12px; font-weight:700;
               background:var(--op-warn-soft); color:var(--op-warn); }
    .op-cantidad { flex-shrink:0; font-size:14px; font-weight:700; }
    .op-entra { color:var(--op-ok); }

    .op-metricas { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
    .op-card { background:var(--op-surface); border:1px solid var(--op-border);
               border-radius:var(--op-r); padding:18px; }
    .op-val { font-size:26px; font-weight:700; letter-spacing:-.8px; line-height:1.05; color:var(--op-text); }
    .op-lbl { font-size:11px; font-weight:700; letter-spacing:.7px; text-transform:uppercase;
              color:var(--op-muted); margin-top:7px; }
    .op-hint { font-size:12px; color:var(--op-muted-2); margin-top:2px; }

    .op-visor { position:fixed; inset:0; z-index:50; display:flex; flex-direction:column;
                align-items:center; justify-content:center; gap:16px; padding:24px;
                background:rgba(0,0,0,.9); }
    .op-visor p { color:#fff; font-size:14px; font-weight:700; text-align:center; max-width:420px; }
    .op-visor video { width:100%; max-width:420px; max-height:60vh; border-radius:18px; object-fit:cover; }
    [x-cloak] { display:none !important; }

    @media (max-width:1100px) { .op-metricas { grid-template-columns:repeat(2,1fr); } }
    @media (max-width:820px)  { .op-cols { grid-template-columns:1fr; } }
    @media (max-width:520px)  { .op-metricas { grid-template-columns:1fr; }
                                .op-btn { flex:1 1 auto; justify-content:center; } }
</style>

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

        {{-- El lector usa el del propio navegador. Donde no existe (iPhone), se
             explica que la cámara del teléfono ya abre la ficha con el QR. --}}
        <button type="button" class="op-btn" x-on:click="abrir">
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
                    this.mensaje = 'Este navegador no lee códigos. Apunta la cámara del teléfono al QR de la gaveta: abre la ficha del producto sola.';
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
