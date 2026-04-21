@extends('portal.layout')
@section('content')

<div class="space-y-6">

    <div>
        <h1 class="text-xl font-bold text-gray-800">Mis cargas</h1>
        <p class="text-sm text-gray-500 mt-0.5">Sigue el estado de tus paquetes y embarques en tiempo real.</p>
    </div>

    @if(session('payment_sent'))
        <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800 font-medium">
            ✓ {{ session('payment_sent') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

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
                    $shipment      = $pkg->shipments->first();
                    $pkgInfo       = \App\Models\LogisticsPackage::ESTADOS[$pkg->estado] ?? [];
                    $estadoPkg     = $pkgInfo['label'] ?? $pkg->estado;
                    $colorPkg      = $pkgInfo['color'] ?? '#6b7280';
                    $estadoEmb     = $shipment ? (\App\Models\LogisticsShipment::ESTADOS[$shipment->estado] ?? null) : null;
                    $colorEmb      = $estadoEmb['color'] ?? '#6b7280';
                    $tieneItems    = $pkg->items->isNotEmpty();
                    $billing       = $pkg->billingRequests->first();
                    $claims        = $claimsByPackage[$pkg->id] ?? [];
                    $lastClaim     = collect($claims)->sortByDesc('created_at')->first();
                    $claimPendiente = $lastClaim && $lastClaim->estado === 'pendiente';
                    $claimVerif     = ($billing && $billing->estado === 'cobrado');
                    $claimRechazado = $lastClaim && $lastClaim->estado === 'rechazado';
                    $mostrarPago    = $billing && in_array($billing->estado, ['aceptado', 'facturado']);
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
                        <div class="flex items-center gap-2 flex-wrap justify-end">
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
                            @if($billing)
                                <button onclick="abrirModal('modal-nv-{{ $pkg->id }}')"
                                        class="inline-flex items-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-700 hover:bg-orange-100 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Nota de venta
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
                            @php
                                $unidadPeso = ($pkg->servicePackage && $pkg->servicePackage->base_cobro === 'peso' && $pkg->servicePackage->unidad_cobro)
                                    ? $pkg->servicePackage->unidad_cobro
                                    : 'kg';
                            @endphp
                            <div>
                                <p class="text-gray-400 uppercase tracking-wide mb-0.5">Peso</p>
                                <p class="font-semibold text-gray-800">{{ $pkg->peso_kg }} {{ $unidadPeso }}</p>
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
                                <p class="text-gray-400 uppercase tracking-wide mb-0.5">Imp. de origen</p>
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
                                @php
                                    $estados   = array_keys(\App\Models\LogisticsShipment::ESTADOS);
                                    $idx       = array_search($shipment->estado, $estados);
                                    $total     = count($estados);
                                    $pct       = $total > 1 ? round(($idx / ($total - 1)) * 100) : 0;
                                @endphp
                                <div class="mt-3">
                                    <div class="flex items-center justify-between text-xs text-gray-400 mb-1">
                                        <span>Solicitado</span><span>{{ $pct }}%</span><span>Entregado</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all duration-500"
                                             style="width:{{ $pct }}%;background-color:{{ $colorEmb }}"></div>
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
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-2">Documentos adjuntos</p>
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
                                                <p class="text-xs text-gray-400">{{ \App\Models\LogisticsDocument::TIPOS[$doc->tipo] ?? $doc->tipo }}</p>
                                            </div>
                                        </div>
                                        @if($doc->archivo_path)
                                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($doc->archivo_path) }}"
                                               target="_blank"
                                               class="ml-3 shrink-0 text-xs text-indigo-600 hover:text-indigo-800 font-medium">Ver →</a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- ── Nota de venta + Verificación de pago ─────────────────── --}}
                    @if($billing)
                    <div class="border-t border-gray-100 px-5 py-4 space-y-4">

                        {{-- Resumen de la nota de venta --}}
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Nota de venta</p>
                                <p class="text-sm font-bold text-gray-800 font-mono mt-0.5">{{ $billing->numero_nota_venta }}</p>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                @php
                                    $bEstados = [
                                        'pendiente' => ['label' => 'Pendiente de aceptación', 'bg' => 'bg-yellow-50', 'text' => 'text-yellow-700', 'border' => 'border-yellow-200'],
                                        'aceptado'  => ['label' => 'Aceptado',  'bg' => 'bg-green-50',  'text' => 'text-green-700',  'border' => 'border-green-200'],
                                        'rechazado' => ['label' => 'Rechazado', 'bg' => 'bg-red-50',    'text' => 'text-red-700',    'border' => 'border-red-200'],
                                        'facturado' => ['label' => 'Facturado', 'bg' => 'bg-blue-50',   'text' => 'text-blue-700',   'border' => 'border-blue-200'],
                                    ];
                                    $bEstado = $bEstados[$billing->estado] ?? $bEstados['pendiente'];
                                @endphp
                                <span class="inline-flex items-center text-xs font-semibold px-2.5 py-0.5 rounded-full border
                                             {{ $bEstado['bg'] }} {{ $bEstado['text'] }} {{ $bEstado['border'] }}">
                                    {{ $bEstado['label'] }}
                                </span>
                                <div class="text-right">
                                    <p class="text-xs text-gray-400">IVA: <span class="font-semibold text-gray-600">${{ number_format($billing->iva, 2) }}</span></p>
                                    <p class="text-sm font-bold text-orange-600">Total: ${{ number_format($billing->total, 2) }}</p>
                                </div>
                                <button onclick="abrirModal('modal-nv-{{ $pkg->id }}')"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-orange-600 hover:text-orange-800 border border-orange-200 rounded-lg px-2.5 py-1 hover:bg-orange-50 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Ver / imprimir
                                </button>
                            </div>
                        </div>

                        {{-- Verificación de pago --}}
                        @if($mostrarPago || $claimVerif)
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-medium mb-2">Verificación de pago</p>

                            @if($claimVerif)
                                {{-- Pago verificado --}}
                                <div class="flex items-center gap-3 rounded-lg bg-green-50 border border-green-200 px-4 py-3">
                                    <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-semibold text-green-800">Pago verificado</p>
                                        <p class="text-xs text-green-600">
                                            Subtotal: ${{ number_format((float)$billing->subtotal_0 + (float)$billing->subtotal_15, 2) }}
                                            &nbsp;·&nbsp; IVA: ${{ number_format($billing->iva, 2) }}
                                            &nbsp;·&nbsp; <strong>Total: ${{ number_format($billing->total, 2) }}</strong>
                                        </p>
                                    </div>
                                </div>

                            @elseif($claimPendiente)
                                {{-- Pago en revisión --}}
                                <div class="flex items-center gap-2 rounded-lg bg-yellow-50 border border-yellow-200 px-4 py-3">
                                    <svg class="w-5 h-5 text-yellow-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-semibold text-yellow-800">Comprobante en revisión</p>
                                        <p class="text-xs text-yellow-600">Monto declarado: ${{ number_format($lastClaim->monto_declarado, 2) }} — Te notificaremos cuando sea verificado.</p>
                                    </div>
                                </div>

                            @else
                                {{-- Formulario de envío de comprobante --}}
                                @if($claimRechazado)
                                <div class="mb-3 rounded-lg bg-red-50 border border-red-200 px-3 py-2.5 text-xs text-red-700">
                                    <strong>Comprobante rechazado.</strong>
                                    @if($lastClaim->notas_verificador) {{ $lastClaim->notas_verificador }}@endif
                                    Sube un nuevo comprobante.
                                </div>
                                @endif

                                <form action="{{ route('portal.payments.store', $empresa->slug) }}"
                                      method="POST"
                                      enctype="multipart/form-data"
                                      class="space-y-3">
                                    @csrf
                                    <input type="hidden" name="package_ids[]" value="{{ $pkg->id }}">
                                    <input type="hidden" name="monto_manual" value="{{ $billing->total }}">

                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">
                                            Comprobante de pago
                                            <span class="text-gray-400 font-normal">(foto o PDF, máx. 10 MB)</span>
                                        </label>
                                        <div class="relative">
                                            <input type="file"
                                                   name="comprobante"
                                                   accept="image/*,.pdf"
                                                   id="comprobante-{{ $pkg->id }}"
                                                   class="hidden"
                                                   onchange="mostrarNombreArchivo(this, 'nombre-{{ $pkg->id }}')">
                                            <label for="comprobante-{{ $pkg->id }}"
                                                   class="flex items-center gap-2 w-full cursor-pointer rounded-lg border-2 border-dashed border-gray-300
                                                          hover:border-indigo-400 bg-gray-50 hover:bg-indigo-50 px-4 py-3 transition text-sm text-gray-500">
                                                <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                <span id="nombre-{{ $pkg->id }}">Seleccionar imagen o PDF…</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Notas (opcional)</label>
                                        <textarea name="notas_cliente" rows="2" placeholder="Ej. Transferencia del banco XYZ del 17/04/2026…"
                                                  class="w-full text-sm rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400"></textarea>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div class="text-xs text-gray-500 space-y-0.5">
                                            @if((float)$billing->subtotal_0 > 0)
                                            <p>Subtotal 0% IVA: <strong class="text-gray-700">${{ number_format($billing->subtotal_0, 2) }}</strong></p>
                                            @endif
                                            <p>Subtotal 15% IVA: <strong class="text-gray-700">${{ number_format($billing->subtotal_15, 2) }}</strong></p>
                                            <p>IVA 15%: <strong class="text-gray-700">${{ number_format($billing->iva, 2) }}</strong></p>
                                            <p>Total a pagar: <strong class="text-orange-600 text-sm">${{ number_format($billing->total, 2) }}</strong></p>
                                        </div>
                                        <button type="submit"
                                                class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                            </svg>
                                            Enviar comprobante
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                        @endif

                    </div>
                    @endif

                </div>

                {{-- ── Modal: Nota de Venta (imprimible) ────────────────────────── --}}
                @if($billing)
                <div id="modal-nv-{{ $pkg->id }}"
                     class="fixed inset-0 z-50 hidden"
                     role="dialog" aria-modal="true">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                         onclick="cerrarModal('modal-nv-{{ $pkg->id }}')"></div>
                    <div class="relative flex items-end sm:items-center justify-center min-h-full p-4">
                        <div class="relative bg-white w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden max-h-[92vh] flex flex-col">

                            {{-- Header --}}
                            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 shrink-0">
                                <h2 class="text-base font-bold text-gray-800">Nota de Venta {{ $billing->numero_nota_venta }}</h2>
                                <div class="flex items-center gap-2">
                                    <button onclick="imprimirNV('nv-print-{{ $pkg->id }}')"
                                            class="inline-flex items-center gap-1 text-xs font-semibold border border-gray-200 rounded-lg px-3 py-1.5 text-gray-600 hover:bg-gray-50 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        Imprimir / PDF
                                    </button>
                                    <button onclick="cerrarModal('modal-nv-{{ $pkg->id }}')"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Contenido de la nota de venta --}}
                            <div class="overflow-y-auto flex-1 p-5" id="nv-print-{{ $pkg->id }}">
                                {{-- Encabezado empresa --}}
                                <div class="rounded-xl bg-gray-900 text-white px-5 py-4 flex justify-between items-start mb-0">
                                    <div>
                                        <p class="font-bold text-base">{{ $empresa->name }}</p>
                                        @if($empresa->numero_identificacion)
                                        <p class="text-gray-400 text-xs mt-0.5">RUC: {{ $empresa->numero_identificacion }}</p>
                                        @endif
                                        @if($empresa->direccion)
                                        <p class="text-gray-400 text-xs mt-0.5">{{ $empresa->direccion }}</p>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <p class="text-orange-400 font-bold text-sm uppercase tracking-wide">Nota de Venta</p>
                                        <p class="font-mono font-bold text-sm mt-1">{{ $billing->numero_nota_venta }}</p>
                                        <p class="text-gray-400 text-xs mt-0.5">{{ $billing->created_at->format('d/m/Y') }}</p>
                                    </div>
                                </div>

                                {{-- Datos del cliente --}}
                                <div class="bg-gray-50 border border-t-0 border-gray-200 px-5 py-3 flex gap-6 flex-wrap">
                                    <div>
                                        <p class="text-xs text-gray-400 uppercase tracking-wide">Cliente</p>
                                        <p class="font-semibold text-gray-800 text-sm">{{ $customer->nombre_completo }}</p>
                                    </div>
                                    @if($customer->cedula_ruc)
                                    <div>
                                        <p class="text-xs text-gray-400 uppercase tracking-wide">Identificación</p>
                                        <p class="font-semibold font-mono text-gray-800 text-sm">{{ $customer->cedula_ruc }}</p>
                                    </div>
                                    @endif
                                </div>

                                {{-- Ítems --}}
                                <div class="border border-t-0 border-gray-200 rounded-b-xl overflow-hidden mb-4">
                                    <table class="w-full text-sm">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs text-gray-500 uppercase font-semibold">Descripción</th>
                                                <th class="px-4 py-2 text-center text-xs text-gray-500 uppercase font-semibold w-16">Cant.</th>
                                                <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase font-semibold w-24">P. Unit.</th>
                                                <th class="px-4 py-2 text-center text-xs text-gray-500 uppercase font-semibold w-14">IVA</th>
                                                <th class="px-4 py-2 text-right text-xs text-gray-500 uppercase font-semibold w-24">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($billing->items as $item)
                                            <tr class="border-t border-gray-100">
                                                <td class="px-4 py-2.5 text-gray-700">
                                                    <span class="text-gray-400 font-mono text-xs mr-1">{{ $item['codigo'] }}</span>
                                                    {{ $item['descripcion'] }}
                                                </td>
                                                <td class="px-4 py-2.5 text-center text-gray-600 text-xs">
                                                    {{ $item['cantidad'] }}
                                                    @if(!empty($item['unidad']))
                                                        <span class="block text-gray-400 text-[10px]">{{ $item['unidad'] }}</span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2.5 text-right text-gray-600 text-xs">${{ number_format($item['precio'] ?? $item['total'], 2) }}</td>
                                                <td class="px-4 py-2.5 text-center text-gray-500 text-xs">{{ $item['iva_pct'] }}%</td>
                                                <td class="px-4 py-2.5 text-right font-semibold text-gray-800">${{ number_format($item['total'], 2) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>

                                    {{-- Totales --}}
                                    <div class="border-t-2 border-gray-200 bg-gray-50 px-5 py-3">
                                        <div class="max-w-[220px] ml-auto space-y-1 text-sm">
                                            @if((float)$billing->subtotal_0 > 0)
                                            <div class="flex justify-between text-gray-500 text-xs">
                                                <span>Subtotal 0% IVA</span>
                                                <span>${{ number_format($billing->subtotal_0, 2) }}</span>
                                            </div>
                                            @endif
                                            <div class="flex justify-between text-gray-500 text-xs">
                                                <span>Subtotal 15% IVA</span>
                                                <span>${{ number_format($billing->subtotal_15, 2) }}</span>
                                            </div>
                                            <div class="flex justify-between text-gray-500 text-xs">
                                                <span>IVA 15%</span>
                                                <span>${{ number_format($billing->iva, 2) }}</span>
                                            </div>
                                            <div class="flex justify-between font-bold text-gray-900 border-t border-gray-300 pt-2">
                                                <span>VALOR TOTAL</span>
                                                <span class="text-orange-600">${{ number_format($billing->total, 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Facturar a --}}
                                @if($billing->billing_nombre)
                                <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3">
                                    <p class="text-xs text-blue-500 uppercase tracking-wide font-medium">Factura a nombre de</p>
                                    <p class="font-bold text-blue-900 text-sm mt-0.5">{{ $billing->billing_nombre }}</p>
                                    @if($billing->billing_ruc)
                                    <p class="text-xs text-blue-600 font-mono mt-0.5">RUC / CI: {{ $billing->billing_ruc }}</p>
                                    @endif
                                    @if($billing->billing_direccion)
                                    <p class="text-xs text-blue-600 mt-0.5">{{ $billing->billing_direccion }}</p>
                                    @endif
                                </div>
                                @endif
                            </div>

                            {{-- Footer --}}
                            <div class="px-5 py-4 border-t border-gray-100 shrink-0">
                                <button onclick="cerrarModal('modal-nv-{{ $pkg->id }}')"
                                        class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2.5 rounded-xl text-sm transition">
                                    Cerrar
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
                @endif

                {{-- Modal productos --}}
                @if($tieneItems)
                <div id="modal-pkg-{{ $pkg->id }}"
                     class="fixed inset-0 z-50 hidden"
                     role="dialog" aria-modal="true">
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                         onclick="cerrarModal('modal-pkg-{{ $pkg->id }}')"></div>
                    <div class="relative flex items-end sm:items-center justify-center min-h-full p-4">
                        <div class="relative bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                            <div class="flex items-start justify-between px-5 py-4 border-b border-gray-100 shrink-0">
                                <div>
                                    <h2 class="text-base font-bold text-gray-800">{{ $pkg->descripcion ?? 'Paquete #' . $pkg->id }}</h2>
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
                                    <span class="text-amber-600 font-medium uppercase tracking-wide block">Impuestos de origen</span>
                                    <span class="text-base font-bold text-amber-700">${{ number_format($pkg->impuestos_amazon, 2) }}</span>
                                </div>
                                @endif
                            </div>
                            @endif
                            <div class="overflow-y-auto flex-1 px-5 py-4 space-y-4">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                                    Artículos — {{ $pkg->items->count() }} {{ $pkg->items->count() === 1 ? 'producto' : 'productos' }}
                                </p>
                                @foreach($pkg->items as $item)
                                <div class="rounded-xl border border-gray-100 bg-gray-50 overflow-hidden">
                                    @if($item->foto_path)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item->foto_path) }}"
                                         alt="{{ $item->nombre }}" class="w-full h-48 object-cover">
                                    @endif
                                    <div class="p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="text-sm font-bold text-gray-800">{{ $item->nombre }}</p>
                                            @if($item->valor)
                                                <span class="shrink-0 text-sm font-extrabold text-indigo-700">${{ number_format($item->valor, 2) }}</span>
                                            @endif
                                        </div>
                                        @if($item->descripcion)
                                            <p class="text-xs text-gray-500 mt-1 leading-relaxed">{{ $item->descripcion }}</p>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                                @php $totalItems = $pkg->items->whereNotNull('valor')->sum('valor'); @endphp
                                @if($pkg->items->count() > 1 && $totalItems > 0)
                                <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 flex items-center justify-between">
                                    <span class="text-xs font-semibold text-indigo-700 uppercase tracking-wide">Total artículos</span>
                                    <span class="text-base font-extrabold text-indigo-700">${{ number_format($totalItems, 2) }}</span>
                                </div>
                                @endif
                            </div>
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

        <div>{{ $packages->links() }}</div>
    @endif

</div>

{{-- Scripts --}}
<script>
function abrirModal(id) {
    var m = document.getElementById(id);
    if (m) { m.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
}
function cerrarModal(id) {
    var m = document.getElementById(id);
    if (m) { m.classList.add('hidden'); document.body.style.overflow = ''; }
}
function mostrarNombreArchivo(input, labelId) {
    var label = document.getElementById(labelId);
    if (label && input.files && input.files[0]) {
        label.textContent = input.files[0].name;
    }
}
function imprimirNV(id) {
    var contenido = document.getElementById(id);
    if (!contenido) return;
    var ventana = window.open('', '_blank', 'width=700,height=900');
    ventana.document.write('<html><head><title>Nota de Venta</title>');
    ventana.document.write('<script src="https://cdn.tailwindcss.com"><\/script>');
    ventana.document.write('</head><body class="p-6 bg-white">');
    ventana.document.write(contenido.innerHTML);
    ventana.document.write('</body></html>');
    ventana.document.close();
    ventana.focus();
    setTimeout(function() { ventana.print(); }, 800);
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('[id^="modal-"]').forEach(function(m) {
            m.classList.add('hidden');
        });
        document.body.style.overflow = '';
    }
});
</script>

@endsection
