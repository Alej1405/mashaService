@extends('portal.layout')
@section('content')

<div class="space-y-5">

    <div class="flex items-center justify-between gap-3">
        <h1 class="text-xl font-bold text-gray-800">Mis órdenes</h1>
        <a href="{{ route('portal.orders.create', $empresa->slug) }}"
           class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold px-4 py-2.5 shadow-sm hover:bg-indigo-700 active:scale-[0.98] transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nuevo pedido
        </a>
    </div>

    @if($orders->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-6 py-14 text-center">
            <div class="mx-auto w-12 h-12 rounded-2xl bg-slate-50 border border-slate-200 grid place-items-center text-slate-300 mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <p class="text-slate-800 text-sm font-medium">Aún no tienes órdenes</p>
            <p class="text-slate-500 text-sm mt-0.5 mb-4">Crea tu primer pedido y aparecerá aquí.</p>
            <a href="{{ route('portal.orders.create', $empresa->slug) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold px-4 py-2.5 shadow-sm shadow-indigo-600/20 hover:bg-indigo-700 active:scale-[0.98] transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuevo pedido
            </a>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
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
