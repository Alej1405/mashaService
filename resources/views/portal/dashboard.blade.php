@extends('portal.layout')
@section('content')

<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Hola, {{ $customer->nombre }}</h1>
        <p class="text-sm text-slate-500 mt-0.5">Este es el estado de tus pedidos y cargas.</p>
    </div>

    {{-- Tarjetas resumen --}}
    <div class="grid {{ $tieneServicios ? 'grid-cols-2' : 'grid-cols-1' }} gap-3 sm:gap-4">
        <a href="{{ route('portal.orders', $empresa->slug) }}"
           class="pv-nav bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 hover:shadow-md hover:-translate-y-0.5 transition block">
            <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 grid place-items-center mb-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </span>
            <p class="text-2xl sm:text-3xl font-bold text-slate-900 leading-none">{{ $totalOrders }}</p>
            <p class="text-[11px] font-semibold text-slate-500 mt-1.5 uppercase tracking-wide">Órdenes</p>
        </a>

        @if($tieneServicios)
        <a href="{{ route('portal.services', $empresa->slug) }}"
           class="pv-nav bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 hover:shadow-md hover:-translate-y-0.5 transition block">
            <span class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 grid place-items-center mb-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <p class="text-2xl sm:text-3xl font-bold text-slate-900 leading-none">{{ $totalContracts }}</p>
            <p class="text-[11px] font-semibold text-slate-500 mt-1.5 uppercase tracking-wide">Servicios</p>
        </a>
        @endif
    </div>

    {{-- Últimas órdenes --}}
    @if($recentOrders->isNotEmpty())
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Últimas órdenes</h2>
            <a href="{{ route('portal.orders', $empresa->slug) }}" class="text-xs text-indigo-600 hover:underline">Ver todas</a>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($recentOrders as $order)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-900"># {{ $order->id }}</p>
                    <p class="text-xs text-slate-500">{{ $order->created_at->format('d/m/Y') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-slate-900">${{ number_format($order->total, 2) }}</p>
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
    @if($tieneServicios && $activeContracts->isNotEmpty())
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-900">Servicios contratados</h2>
            <a href="{{ route('portal.services', $empresa->slug) }}" class="text-xs text-indigo-600 hover:underline">Ver todos</a>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($activeContracts as $contract)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-900">{{ $contract->nombre_servicio }}</p>
                    <p class="text-xs text-slate-500">
                        Desde {{ $contract->fecha_inicio->format('d/m/Y') }}
                        @if($contract->fecha_fin) · Hasta {{ $contract->fecha_fin->format('d/m/Y') }} @endif
                    </p>
                </div>
                @if($contract->precio)
                <p class="text-sm font-semibold text-slate-900">${{ number_format($contract->precio, 2) }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Catálogo de productos --}}
    @if($catalogoProductos->isNotEmpty())
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-900">Nuestros productos</h2>
            <p class="text-xs text-slate-400 mt-0.5">Contáctanos para realizar tu pedido.</p>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($catalogoProductos as $producto)
            <div class="px-5 py-4">
                <div class="flex items-center gap-4">
                    @if($producto->imagen_principal)
                        <img src="{{ asset('storage/' . ltrim($producto->imagen_principal, '/')) }}" alt="{{ $producto->nombre }}"
                             class="w-12 h-12 rounded-lg object-cover border border-slate-100 shrink-0">
                    @else
                        <div class="w-12 h-12 rounded-lg bg-gray-50 border border-slate-100 shrink-0 flex items-center justify-center text-slate-300">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5 12 3 3.75 7.5m16.5 0L12 12m8.25-4.5v9L12 21m0-9L3.75 7.5m0 0v9L12 21"/></svg>
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-900">{{ $producto->nombre }}</p>
                        @if($producto->descripcion)
                            <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($producto->descripcion), 90) }}</p>
                        @endif
                    </div>
                    @if($producto->precio_venta)
                        <div class="shrink-0 text-right">
                            <p class="text-base font-bold text-indigo-700">${{ number_format($producto->precio_venta, 2) }}</p>
                        </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

@endsection
