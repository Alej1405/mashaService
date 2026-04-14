@extends('portal.layout')
@section('content')

<div class="space-y-5">

    <h1 class="text-xl font-bold text-gray-800">Mis órdenes</h1>

    @if($orders->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 px-6 py-12 text-center">
            <p class="text-gray-500 text-sm">Aún no tienes órdenes registradas.</p>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            <th class="px-5 py-3">N° Orden</th>
                            <th class="px-5 py-3">Fecha</th>
                            <th class="px-5 py-3">Total</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($orders as $order)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-5 py-3 font-medium text-gray-800">#{{ $order->id }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ $order->created_at->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 font-semibold text-gray-800">${{ number_format($order->total, 2) }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold
                                    @if($order->estado === 'entregada') bg-green-100 text-green-700
                                    @elseif($order->estado === 'cancelada') bg-red-100 text-red-700
                                    @elseif($order->estado === 'enviada') bg-sky-100 text-sky-700
                                    @elseif($order->estado === 'confirmada') bg-indigo-100 text-indigo-700
                                    @else bg-amber-100 text-amber-700
                                    @endif">
                                    {{ ucfirst($order->estado) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('portal.orders.show', [$empresa->slug, $order->id]) }}"
                                   class="text-xs text-indigo-600 hover:underline">Ver detalle</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $orders->links() }}</div>
    @endif

</div>

@endsection
