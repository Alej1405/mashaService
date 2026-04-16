@extends('portal.layout')
@section('content')

<div class="space-y-6">

    <div>
        <h1 class="text-xl font-bold text-gray-800">Mis cargas</h1>
        <p class="text-sm text-gray-500 mt-0.5">Sigue el estado de tus paquetes y embarques en tiempo real.</p>
    </div>

    @if($packages->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 px-6 py-12 text-center">
            <svg class="mx-auto w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <p class="text-sm text-gray-500">Aún no tienes paquetes registrados.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($packages as $pkg)
                @php
                    $shipment   = $pkg->shipments->first();
                    $pkgInfo    = \App\Models\LogisticsPackage::ESTADOS[$pkg->estado] ?? [];
                    $estadoPkg  = $pkgInfo['label'] ?? $pkg->estado;
                    $colorPkg   = $pkgInfo['color'] ?? '#6b7280';
                    $estadoEmb  = $shipment ? (\App\Models\LogisticsShipment::ESTADOS[$shipment->estado] ?? null) : null;
                    $colorEmb   = $estadoEmb['color'] ?? '#6b7280';
                    $tieneItems = $pkg->items->isNotEmpty();
                @endphp
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

                    {{-- Header de la tarjeta --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">
                                {{ $pkg->descripcion ?? 'Paquete #' . $pkg->id }}
                            </p>
                            @if($pkg->numero_tracking)
                                <p class="text-xs text-gray-500 mt-0.5 font-mono">{{ $pkg->numero_tracking }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            @if($tieneItems)
                                <button onclick="abrirModal('modal-pkg-{{ $pkg->id }}')"
                                        class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Ver productos ({{ $pkg->items->count() }})
                                </button>
                            @endif
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                  style="background-color:{{ $colorPkg }}18;color:{{ $colorPkg }};border:1px solid {{ $colorPkg }}44;">
                                {{ $estadoPkg }}
                            </span>
                        </div>
                    </div>

                    {{-- Detalles --}}
                    <div class="px-5 py-4 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs text-gray-600">
                        @if($pkg->peso_kg)
                            <div>
                                <p class="text-gray-400 uppercase tracking-wide mb-0.5">Peso</p>
                                <p class="font-semibold text-gray-800">{{ $pkg->peso_kg }} kg</p>
                            </div>
                        @endif
                        @if($pkg->valor_declarado)
                            <div>
                                <p class="text-gray-400 uppercase tracking-wide mb-0.5">Valor declarado</p>
                                <p class="font-semibold text-gray-800">${{ number_format($pkg->valor_declarado, 2) }}</p>
                            </div>
                        @endif
                        @if($pkg->gastos_envio)
                            <div>
                                <p class="text-gray-400 uppercase tracking-wide mb-0.5">Gastos de envío</p>
                                <p class="font-semibold text-gray-800">${{ number_format($pkg->gastos_envio, 2) }}</p>
                            </div>
                        @endif
                        @if($pkg->impuestos_amazon)
                            <div>
                                <p class="text-gray-400 uppercase tracking-wide mb-0.5">Imp. Amazon</p>
                                <p class="font-semibold text-gray-800">${{ number_format($pkg->impuestos_amazon, 2) }}</p>
                            </div>
                        @endif
                        @if($pkg->fecha_recepcion_bodega)
                            <div>
                                <p class="text-gray-400 uppercase tracking-wide mb-0.5">Recibido en bodega</p>
                                <p class="font-semibold text-gray-800">{{ $pkg->fecha_recepcion_bodega->format('d/m/Y') }}</p>
                            </div>
                        @endif
                        @if($pkg->referencia)
                            <div>
                                <p class="text-gray-400 uppercase tracking-wide mb-0.5">Referencia</p>
                                <p class="font-semibold text-gray-800">{{ $pkg->referencia }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Embarque asociado --}}
                    @if($shipment)
                        <div class="px-5 pb-4">
                            <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                                <p class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-2">Embarque</p>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-bold text-gray-800">{{ $shipment->numero_embarque }}</p>
                                        @if($shipment->fecha_embarque)
                                            <p class="text-xs text-gray-500">Salida: {{ $shipment->fecha_embarque->format('d/m/Y') }}</p>
                                        @endif
                                        @if($shipment->fecha_llegada_ecuador)
                                            <p class="text-xs text-gray-500">Llegada estimada: {{ $shipment->fecha_llegada_ecuador->format('d/m/Y') }}</p>
                                        @endif
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold text-white"
                                          style="background-color: {{ $colorEmb }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white/60 inline-block"></span>
                                        {{ $estadoEmb['label'] ?? $shipment->estado }}
                                    </span>
                                </div>

                                {{-- Barra de progreso de estados --}}
                                @php
                                    $estados   = array_keys(\App\Models\LogisticsShipment::ESTADOS);
                                    $idx       = array_search($shipment->estado, $estados);
                                    $total     = count($estados);
                                    $pct       = $total > 1 ? round(($idx / ($total - 1)) * 100) : 0;
                                @endphp
                                <div class="mt-3">
                                    <div class="flex items-center justify-between text-xs text-gray-400 mb-1">
                                        <span>Solicitado</span>
                                        <span>{{ $pct }}%</span>
                                        <span>Entregado</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all duration-500"
                                             style="width: {{ $pct }}%; background-color: {{ $colorEmb }}"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="px-5 pb-2">
                            <p class="text-xs text-gray-400 italic">Este paquete aún no ha sido asignado a un embarque.</p>
                        </div>
                    @endif

                    {{-- Documentos adjuntos --}}
                    @if($pkg->documents->isNotEmpty())
                        <div class="px-5 pb-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-2">
                                Documentos adjuntos
                            </p>
                            <div class="space-y-1.5">
                                @foreach($pkg->documents as $doc)
                                    <div class="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <div class="min-w-0">
                                                <p class="text-xs font-medium text-gray-800 truncate">{{ $doc->nombre }}</p>
                                                <p class="text-xs text-gray-400">
                                                    {{ \App\Models\LogisticsDocument::TIPOS[$doc->tipo] ?? $doc->tipo }}
                                                </p>
                                            </div>
                                        </div>
                                        @if($doc->archivo_path)
                                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($doc->archivo_path) }}"
                                               target="_blank"
                                               class="ml-3 shrink-0 text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                                Ver →
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>

                {{-- Modal de detalle del paquete --}}
                @if($tieneItems)
                <div id="modal-pkg-{{ $pkg->id }}"
                     class="fixed inset-0 z-50 hidden"
                     role="dialog" aria-modal="true">

                    {{-- Overlay --}}
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                         onclick="cerrarModal('modal-pkg-{{ $pkg->id }}')"></div>

                    {{-- Panel del modal --}}
                    <div class="relative flex items-end sm:items-center justify-center min-h-full p-4">
                        <div class="relative bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden
                                    max-h-[90vh] flex flex-col">

                            {{-- Header --}}
                            <div class="flex items-start justify-between px-5 py-4 border-b border-gray-100 shrink-0">
                                <div>
                                    <h2 class="text-base font-bold text-gray-800">
                                        {{ $pkg->descripcion ?? 'Paquete #' . $pkg->id }}
                                    </h2>
                                    @if($pkg->numero_tracking)
                                        <p class="text-xs text-gray-500 font-mono mt-0.5">{{ $pkg->numero_tracking }}</p>
                                    @endif
                                </div>
                                <button onclick="cerrarModal('modal-pkg-{{ $pkg->id }}')"
                                        class="ml-4 shrink-0 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>

                            {{-- Costos adicionales del paquete (si existen) --}}
                            @if($pkg->gastos_envio || $pkg->impuestos_amazon)
                            <div class="px-5 py-3 bg-amber-50 border-b border-amber-100 flex flex-wrap gap-4 shrink-0">
                                @if($pkg->gastos_envio)
                                <div class="text-xs">
                                    <span class="text-amber-600 font-medium uppercase tracking-wide block">Gastos de envío</span>
                                    <span class="text-base font-bold text-amber-700">${{ number_format($pkg->gastos_envio, 2) }}</span>
                                </div>
                                @endif
                                @if($pkg->impuestos_amazon)
                                <div class="text-xs">
                                    <span class="text-amber-600 font-medium uppercase tracking-wide block">Impuestos Amazon</span>
                                    <span class="text-base font-bold text-amber-700">${{ number_format($pkg->impuestos_amazon, 2) }}</span>
                                </div>
                                @endif
                            </div>
                            @endif

                            {{-- Lista de productos (scrolleable) --}}
                            <div class="overflow-y-auto flex-1 px-5 py-4 space-y-4">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Artículos — {{ $pkg->items->count() }} {{ $pkg->items->count() === 1 ? 'producto' : 'productos' }}
                                </p>

                                @foreach($pkg->items as $item)
                                <div class="rounded-xl border border-gray-100 bg-gray-50 overflow-hidden">
                                    @if($item->foto_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->foto_path) }}"
                                         alt="{{ $item->nombre }}"
                                         class="w-full h-48 object-cover">
                                    @endif
                                    <div class="p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="text-sm font-bold text-gray-800">{{ $item->nombre }}</p>
                                            @if($item->valor)
                                                <span class="shrink-0 text-sm font-extrabold text-indigo-700">
                                                    ${{ number_format($item->valor, 2) }}
                                                </span>
                                            @endif
                                        </div>
                                        @if($item->descripcion)
                                            <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $item->descripcion }}</p>
                                        @endif
                                    </div>
                                </div>
                                @endforeach

                                {{-- Resumen de valores si hay varios ítems con valor --}}
                                @php $totalItems = $pkg->items->whereNotNull('valor')->sum('valor'); @endphp
                                @if($pkg->items->count() > 1 && $totalItems > 0)
                                <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 flex items-center justify-between">
                                    <span class="text-xs font-semibold text-indigo-700 uppercase tracking-wide">Total artículos</span>
                                    <span class="text-base font-extrabold text-indigo-700">${{ number_format($totalItems, 2) }}</span>
                                </div>
                                @endif
                            </div>

                            {{-- Footer --}}
                            <div class="px-5 py-4 border-t border-gray-100 shrink-0">
                                <button onclick="cerrarModal('modal-pkg-{{ $pkg->id }}')"
                                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-xl text-sm transition">
                                    Cerrar
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
                @endif

            @endforeach
        </div>

        <div>
            {{ $packages->links() }}
        </div>
    @endif

</div>

{{-- Scripts de modales --}}
<script>
function abrirModal(id) {
    var modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}
function cerrarModal(id) {
    var modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('[id^="modal-pkg-"]').forEach(function(m) {
            m.classList.add('hidden');
        });
        document.body.style.overflow = '';
    }
});
</script>

@endsection
