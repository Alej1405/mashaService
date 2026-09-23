@php
    use App\Filament\Operaciones\Pages\Dashboard;
    use App\Filament\Operaciones\Pages\RegistrarMovimiento;
    use App\Filament\Operaciones\Resources\InventarioResource;

    $empresa = \Filament\Facades\Filament::getTenant();

    if (! $empresa) {
        return;
    }

    $ruta = request()->path();

    // El diseño manda cuatro destinos: Bodega, Buscar, Escanear y Movimiento.
    // "Kardex" del Figma vive dentro de la ficha de cada ítem, no como pantalla
    // suelta, así que ese lugar lo ocupa lo que de verdad se hace en bodega.
    $destinos = [
        [
            'etiqueta' => 'Bodega',
            'url'      => Dashboard::getUrl(tenant: $empresa),
            'activo'   => str_ends_with($ruta, $empresa->slug),
            'icono'    => 'M2.25 12l8.954-8.955a1.5 1.5 0 012.122 0L22.28 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75',
        ],
        [
            'etiqueta' => 'Buscar',
            'url'      => InventarioResource::getUrl(tenant: $empresa),
            'activo'   => str_contains($ruta, '/inventarios'),
            'icono'    => 'm21 21-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z',
        ],
        [
            'etiqueta' => 'Escanear',
            'url'      => null, // lo abre el lector, o explica el QR donde no hay
            'activo'   => false,
            'icono'    => 'M3.75 4.875A1.125 1.125 0 014.875 3.75h2.25M20.25 4.875A1.125 1.125 0 0019.125 3.75h-2.25M3.75 19.125c0 .621.504 1.125 1.125 1.125h2.25M20.25 19.125c0 .621-.504 1.125-1.125 1.125h-2.25M3 12h18',
        ],
        [
            'etiqueta' => 'Movimiento',
            'url'      => RegistrarMovimiento::getUrl(tenant: $empresa),
            'activo'   => str_contains($ruta, 'registrar-movimiento'),
            'icono'    => 'M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5',
        ],
    ];
@endphp

{{-- Navegación inferior: en el celular y en la tablet esto es una app, no una
     web con menú lateral. El sidebar de Filament se esconde por completo. --}}
<div x-data="navInferiorBodega()">
    <style>
        .nav-inferior { display:none; }

        @media (max-width:1023px) {
            /* Fuera el menú lateral, su hamburguesa y la X de cerrarlo: la
               navegación es la de abajo */
            .fi-sidebar, .fi-topbar-open-sidebar-btn, .fi-sidebar-close-overlay,
            .fi-sidebar-close-btn, .fi-topbar-close-sidebar-btn { display:none !important; }

            /* Dedos, no punteros: los botones de 36 px suben a 44 */
            .fi-btn, .fi-ac-btn-action, .fi-ta-actions .fi-icon-btn, .fi-pagination-item {
                min-height:44px !important;
            }
            .fi-btn { padding-top:.6rem !important; padding-bottom:.6rem !important; }

            /* Las acciones que ya viven en la barra inferior no se repiten arriba */
            .op-barra .op-btn { display:none !important; }
            .op-barra { gap:0; }
            .fi-main-ctn { margin-inline-start:0 !important; }
            .fi-main { padding-bottom:calc(76px + env(safe-area-inset-bottom)) !important; }

            .nav-inferior {
                position:fixed; left:0; right:0; bottom:0; z-index:40;
                display:grid; grid-template-columns:repeat(4,1fr);
                padding:6px 6px calc(6px + env(safe-area-inset-bottom));
                background:rgba(255,255,255,.94);
                backdrop-filter:blur(14px); -webkit-backdrop-filter:blur(14px);
                border-top:1px solid #e2e8f0;
                box-shadow:0 -6px 20px -12px rgba(15,23,42,.35);
                font-family:'Sansation', ui-sans-serif, system-ui, sans-serif;
            }
            .nav-inferior a, .nav-inferior button {
                display:flex; flex-direction:column; align-items:center; gap:3px;
                padding:8px 4px 6px; border:0; background:none; cursor:pointer;
                font-family:inherit; font-size:11px; font-weight:700; letter-spacing:.2px;
                color:#64748b; text-decoration:none; border-radius:12px;
                transition:color .15s ease, background .15s ease;
            }
            .nav-inferior a:active, .nav-inferior button:active { background:#f1f5f9; }
            .nav-inferior .es-activo { color:#4f46e5; }
            .nav-inferior svg { width:22px; height:22px; stroke-width:1.7; }
        }

        .nav-visor {
            position:fixed; inset:0; z-index:60; display:flex; flex-direction:column;
            align-items:center; justify-content:center; gap:16px; padding:24px;
            background:rgba(0,0,0,.92);
        }
        .nav-visor p { color:#fff; font-size:14px; font-weight:700; text-align:center; max-width:340px; line-height:1.5; }
        .nav-visor video { width:100%; max-width:420px; max-height:58vh; border-radius:18px; object-fit:cover; }
        .nav-visor button { padding:12px 28px; border:0; border-radius:12px; background:#fff;
                            font-family:inherit; font-size:14px; font-weight:700; color:#0f172a; }
        [x-cloak] { display:none !important; }
    </style>

    <nav class="nav-inferior" aria-label="Navegación de bodega">
        @foreach ($destinos as $destino)
            @if ($destino['url'])
                <a href="{{ $destino['url'] }}" class="{{ $destino['activo'] ? 'es-activo' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $destino['icono'] }}"/>
                    </svg>
                    {{ $destino['etiqueta'] }}
                </a>
            @else
                <button type="button" x-on:click="abrir">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $destino['icono'] }}"/>
                    </svg>
                    {{ $destino['etiqueta'] }}
                </button>
            @endif
        @endforeach
    </nav>

    <div class="nav-visor" x-show="abierto" x-cloak>
        <p x-text="mensaje"></p>
        <video x-ref="video" x-show="soportado" autoplay muted playsinline></video>
        <button type="button" x-on:click="cerrar">Cerrar</button>
    </div>
</div>

<script>
    function navInferiorBodega() {
        return {
            abierto: false,
            soportado: 'BarcodeDetector' in window,
            mensaje: '',
            flujo: null,

            async abrir() {
                this.abierto = true;

                if (! this.soportado) {
                    // En iPhone y iPad no existe el lector del navegador: el QR
                    // de la gaveta se abre con la cámara del sistema.
                    this.mensaje = 'Apunta la cámara de tu teléfono al QR de la gaveta: abre la ficha del producto sola.';

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
                window.location = valor.startsWith('http')
                    ? valor
                    : @js(InventarioResource::getUrl(tenant: $empresa)) + '?tableSearch=' + encodeURIComponent(valor);
            },

            cerrar() {
                this.abierto = false;
                this.flujo?.getTracks().forEach((t) => t.stop());
                this.flujo = null;
            },
        }
    }
</script>
