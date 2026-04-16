@extends('portal.layout')
@section('content')

<div class="space-y-6">

    <div>
        <h1 class="text-xl font-bold text-gray-800">Bienvenido, {{ $customer->nombre }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">Aquí puedes seguir el estado de tus cargas y servicios.</p>
    </div>

    {{-- Tarjetas resumen --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Mis cargas</p>
            <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalPackages }}</p>
            <a href="{{ route('portal.packages', $empresa->slug) }}" class="text-xs text-indigo-600 hover:underline mt-2 inline-block">Ver todas →</a>
        </div>
        <div class="bg-white rounded-xl border {{ $pendingPackages->isNotEmpty() ? 'border-amber-300 bg-amber-50' : 'border-gray-200' }} p-5">
            <p class="text-xs {{ $pendingPackages->isNotEmpty() ? 'text-amber-600' : 'text-gray-500' }} font-medium uppercase tracking-wide">Por pagar</p>
            <p class="text-3xl font-bold {{ $pendingPackages->isNotEmpty() ? 'text-amber-700' : 'text-gray-800' }} mt-1">{{ $pendingPackages->count() }}</p>
            @if($totalPendingPago > 0)
                <p class="text-xs font-bold text-amber-600 mt-1">${{ number_format($totalPendingPago, 2) }}</p>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Órdenes</p>
            <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalOrders }}</p>
            @if($totalOrders > 0)
                <a href="{{ route('portal.orders', $empresa->slug) }}" class="text-xs text-indigo-600 hover:underline mt-2 inline-block">Ver todas →</a>
            @endif
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Servicios activos</p>
            <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalContracts }}</p>
            @if($totalContracts > 0)
                <a href="{{ route('portal.services', $empresa->slug) }}" class="text-xs text-indigo-600 hover:underline mt-2 inline-block">Ver todos →</a>
            @endif
        </div>
    </div>

    {{-- Cargas pendientes de pago --}}
    <div class="bg-white rounded-xl border {{ $pendingPackages->isNotEmpty() ? 'border-amber-200' : 'border-gray-200' }} overflow-hidden">
        <div class="px-5 py-4 border-b {{ $pendingPackages->isNotEmpty() ? 'border-amber-100 bg-amber-50' : 'border-gray-100' }} flex items-center justify-between">
            <div class="flex items-center gap-2">
                @if($pendingPackages->isNotEmpty())
                    <span class="w-2 h-2 rounded-full bg-amber-500 inline-block animate-pulse"></span>
                @endif
                <h2 class="text-sm font-semibold {{ $pendingPackages->isNotEmpty() ? 'text-amber-800' : 'text-gray-700' }}">
                    Cargas pendientes de pago
                </h2>
            </div>
            @if($pendingPackages->isNotEmpty())
                <span class="text-xs font-bold text-amber-700 bg-amber-100 border border-amber-200 rounded-full px-2.5 py-0.5">
                    {{ $pendingPackages->count() }} {{ $pendingPackages->count() === 1 ? 'carga' : 'cargas' }}
                </span>
            @endif
        </div>

        @if(session('payment_sent'))
            <div class="px-5 py-4 bg-green-50 border-b border-green-100 flex items-center gap-2 text-sm text-green-800">
                <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('payment_sent') }}
            </div>
        @endif

        @if($pendingPackages->isEmpty())
            <div class="px-5 py-8 text-center">
                <svg class="mx-auto w-8 h-8 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-gray-400">No tienes cargas pendientes de pago.</p>
            </div>
        @else
            {{-- Lista de cargas --}}
            <div class="divide-y divide-gray-100">
                @foreach($pendingPackages as $pkg)
                <div class="px-5 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">
                            {{ $pkg->descripcion ?? 'Paquete #' . $pkg->id }}
                        </p>
                        @if($pkg->numero_tracking)
                            <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $pkg->numero_tracking }}</p>
                        @endif
                    </div>
                    <p class="text-base font-bold text-amber-600">
                        {{ $pkg->monto_cobro ? '$'.number_format($pkg->monto_cobro, 2) : 'Por definir' }}
                    </p>
                </div>
                @endforeach
            </div>

            {{-- Total + cuentas bancarias --}}
            <div class="px-5 py-4 bg-amber-50 border-t border-amber-100">
                @if($totalPendingPago > 0)
                <div class="flex items-center justify-between mb-4">
                    <span class="text-sm font-semibold text-amber-800">Total a pagar</span>
                    <span class="text-xl font-extrabold text-amber-700">${{ number_format($totalPendingPago, 2) }}</span>
                </div>
                @endif

                @if($cuentasBancarias->isNotEmpty())
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Realiza tu pago por transferencia a:</p>
                <div class="space-y-2 mb-4">
                    @foreach($cuentasBancarias as $cuenta)
                    <div class="bg-white border border-amber-100 rounded-lg px-4 py-3 text-xs text-gray-700 grid grid-cols-2 gap-x-4 gap-y-1">
                        <div>
                            <span class="text-gray-400 uppercase tracking-wide block">Banco</span>
                            <span class="font-semibold text-gray-800">{{ $cuenta->bank?->nombre ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 uppercase tracking-wide block">Tipo</span>
                            <span class="font-semibold text-gray-800">{{ $cuenta->tipo_cuenta === 'ahorros' ? 'Ahorros' : 'Corriente' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 uppercase tracking-wide block">N.° de cuenta</span>
                            <span class="font-semibold text-gray-800 font-mono">{{ $cuenta->numero_cuenta }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 uppercase tracking-wide block">Titular</span>
                            <span class="font-semibold text-gray-800">{{ $cuenta->nombre_titular }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Botón Pagar --}}
                <button id="btn-pagar"
                        onclick="document.getElementById('form-pago').classList.toggle('hidden')"
                        class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-6 rounded-xl transition text-sm flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    Registrar mi pago
                </button>

                {{-- Formulario de pago --}}
                <div id="form-pago" class="hidden mt-4">
                    <form action="{{ route('portal.payments.store', $empresa->slug) }}"
                          method="POST"
                          enctype="multipart/form-data"
                          class="space-y-4">
                        @csrf

                        {{-- Selección de cargas --}}
                        <div class="bg-white rounded-xl border border-amber-100 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">¿Por qué cargas pagas?</p>
                                <label class="flex items-center gap-1.5 text-xs text-amber-700 cursor-pointer">
                                    <input type="checkbox" id="select-all"
                                           onchange="document.querySelectorAll('.pkg-check').forEach(c => c.checked = this.checked)"
                                           class="rounded border-amber-300 text-amber-500">
                                    Seleccionar todas
                                </label>
                            </div>
                            <div class="space-y-2">
                                @foreach($pendingPackages as $pkg)
                                <label class="flex items-center justify-between bg-amber-50 rounded-lg px-3 py-2 cursor-pointer hover:bg-amber-100 transition">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox"
                                               name="package_ids[]"
                                               value="{{ $pkg->id }}"
                                               class="pkg-check rounded border-amber-300 text-amber-500"
                                               checked>
                                        <div>
                                            <p class="text-xs font-semibold text-gray-800">{{ $pkg->descripcion ?? 'Paquete #'.$pkg->id }}</p>
                                            @if($pkg->numero_tracking)
                                                <p class="text-[10px] text-gray-400 font-mono">{{ $pkg->numero_tracking }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold text-amber-700 ml-2 shrink-0">
                                        {{ $pkg->monto_cobro ? '$'.number_format($pkg->monto_cobro, 2) : '—' }}
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        @if($errors->has('package_ids'))
                            <p class="text-xs text-red-600">{{ $errors->first('package_ids') }}</p>
                        @endif

                        {{-- Monto declarado --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">
                                Monto transferido <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">$</span>
                                <input type="number" name="monto_manual" step="0.01" min="0.01" required
                                       placeholder="0.00"
                                       value="{{ $totalPendingPago > 0 ? number_format($totalPendingPago, 2, '.', '') : '' }}"
                                       class="w-full border border-gray-200 rounded-lg pl-7 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                            </div>
                            @error('monto_manual')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Comprobante --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">
                                Foto del comprobante <span class="text-gray-400 font-normal">(opcional)</span>
                            </label>
                            <input type="file" name="comprobante" accept="image/*"
                                   class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                            <p class="text-[10px] text-gray-400 mt-1">PNG, JPG o WEBP. Máx 5 MB.</p>
                        </div>

                        {{-- Notas --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">
                                Referencia / Notas <span class="text-gray-400 font-normal">(opcional)</span>
                            </label>
                            <textarea name="notas_cliente" rows="2"
                                      placeholder="Ej: Transferencia realizada el 15/04 a las 10:00 am"
                                      class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none"></textarea>
                        </div>

                        <button type="submit"
                                class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-3 rounded-xl text-sm transition">
                            Confirmar registro de pago
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>

    {{-- Últimas cargas --}}
    @if($recentPackages->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-800">Últimas cargas</h2>
            <a href="{{ route('portal.packages', $empresa->slug) }}" class="text-xs text-indigo-600 hover:underline">Ver todas</a>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($recentPackages as $pkg)
            @php
                $pkgInfo   = \App\Models\LogisticsPackage::ESTADOS[$pkg->estado] ?? [];
                $pkgLabel  = $pkgInfo['label'] ?? $pkg->estado;
                $pkgColor  = $pkgInfo['color'] ?? '#6b7280';
            @endphp
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $pkg->descripcion ?? 'Paquete #' . $pkg->id }}</p>
                    @if($pkg->numero_tracking)
                        <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $pkg->numero_tracking }}</p>
                    @endif
                </div>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold shrink-0 ml-3"
                      style="background-color:{{ $pkgColor }}18;color:{{ $pkgColor }};border:1px solid {{ $pkgColor }}44;">
                    {{ $pkgLabel }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Últimas órdenes --}}
    @if($recentOrders->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-800">Últimas órdenes</h2>
            <a href="{{ route('portal.orders', $empresa->slug) }}" class="text-xs text-indigo-600 hover:underline">Ver todas</a>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($recentOrders as $order)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-800"># {{ $order->id }}</p>
                    <p class="text-xs text-gray-500">{{ $order->created_at->format('d/m/Y') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-800">${{ number_format($order->total, 2) }}</p>
                    <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold
                        @if($order->estado === 'entregada') bg-green-100 text-green-700
                        @elseif($order->estado === 'cancelada') bg-red-100 text-red-700
                        @elseif($order->estado === 'enviada') bg-sky-100 text-sky-700
                        @else bg-amber-100 text-amber-700
                        @endif">
                        {{ ucfirst($order->estado) }}
                    </span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Servicios activos --}}
    @if($activeContracts->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-800">Servicios contratados</h2>
            <a href="{{ route('portal.services', $empresa->slug) }}" class="text-xs text-indigo-600 hover:underline">Ver todos</a>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($activeContracts as $contract)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $contract->nombre_servicio }}</p>
                    <p class="text-xs text-gray-500">
                        Desde {{ $contract->fecha_inicio->format('d/m/Y') }}
                        @if($contract->fecha_fin) · Hasta {{ $contract->fecha_fin->format('d/m/Y') }} @endif
                    </p>
                </div>
                @if($contract->precio)
                <p class="text-sm font-semibold text-gray-800">${{ number_format($contract->precio, 2) }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

@endsection
