@extends('portal.layout')
@section('content')

<div class="space-y-6">

    <div>
        <h1 class="text-xl font-bold text-gray-800">Bienvenido, {{ $customer->nombre }}</h1>
        <p class="text-sm text-gray-500 mt-0.5">Aquí puedes ver tus órdenes y servicios contratados.</p>
    </div>

    {{-- Tarjetas resumen --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Órdenes totales</p>
            <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalOrders }}</p>
            <a href="{{ route('portal.orders', $empresa->slug) }}" class="text-xs text-indigo-600 hover:underline mt-2 inline-block">Ver todas →</a>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Servicios activos</p>
            <p class="text-3xl font-bold text-gray-800 mt-1">{{ $totalContracts }}</p>
            <a href="{{ route('portal.services', $empresa->slug) }}" class="text-xs text-indigo-600 hover:underline mt-2 inline-block">Ver todos →</a>
        </div>
    </div>

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
