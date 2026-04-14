@extends('portal.layout')
@section('content')

<div class="space-y-5">

    <div class="flex items-center gap-3">
        <a href="{{ route('portal.orders', $empresa->slug) }}" class="text-sm text-indigo-600 hover:underline">← Volver</a>
        <h1 class="text-xl font-bold text-gray-800">Orden #{{ $order->id }}</h1>
        <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold
            @if($order->estado === 'entregada') bg-green-100 text-green-700
            @elseif($order->estado === 'cancelada') bg-red-100 text-red-700
            @elseif($order->estado === 'enviada') bg-sky-100 text-sky-700
            @elseif($order->estado === 'confirmada') bg-indigo-100 text-indigo-700
            @else bg-amber-100 text-amber-700
            @endif">
            {{ ucfirst($order->estado) }}
        </span>
    </div>

    {{-- Ítems --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-800">Productos</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($order->orderItems as $item)
            <div class="px-5 py-3 flex items-center justify-between gap-4">
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-800">{{ $item->product?->nombre ?? 'Producto eliminado' }}</p>
                    <p class="text-xs text-gray-500">Cantidad: {{ $item->cantidad }} × ${{ number_format($item->precio_unitario, 2) }}</p>
                </div>
                <p class="text-sm font-semibold text-gray-800">${{ number_format($item->subtotal, 2) }}</p>
            </div>
            @endforeach
        </div>
        <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 space-y-1">
            <div class="flex justify-between text-sm text-gray-600">
                <span>Subtotal</span>
                <span>${{ number_format($order->subtotal, 2) }}</span>
            </div>
            @if($order->descuento > 0)
            <div class="flex justify-between text-sm text-green-700">
                <span>Descuento @if($order->coupon)({{ $order->coupon->codigo }})@endif</span>
                <span>-${{ number_format($order->descuento, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between text-base font-bold text-gray-800 pt-1 border-t border-gray-200">
                <span>Total</span>
                <span>${{ number_format($order->total, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Datos de la orden --}}
    <div class="bg-white rounded-xl border border-gray-200 px-5 py-4 space-y-2 text-sm text-gray-600">
        <div class="flex gap-2"><span class="font-semibold text-gray-700 w-28">Fecha:</span> {{ $order->created_at->format('d/m/Y H:i') }}</div>
        @if($order->notas)
        <div class="flex gap-2"><span class="font-semibold text-gray-700 w-28">Notas:</span> {{ $order->notas }}</div>
        @endif
    </div>

</div>

@endsection
