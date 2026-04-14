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
                    $estadoPkg  = \App\Models\LogisticsPackage::ESTADOS[$pkg->estado] ?? $pkg->estado;
                    $estadoEmb  = $shipment ? (\App\Models\LogisticsShipment::ESTADOS[$shipment->estado] ?? null) : null;
                    $colorEmb   = $estadoEmb['color'] ?? '#6b7280';
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
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-gray-100 text-gray-700">
                            {{ $estadoPkg }}
                        </span>
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
            @endforeach
        </div>

        <div>
            {{ $packages->links() }}
        </div>
    @endif

</div>

@endsection
