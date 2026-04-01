@extends('mobile.layout')
@section('title', 'Inventario')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.index') }}"
       class="w-8 h-8 flex items-center justify-center rounded-xl"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div class="flex-1">
        <h2 class="text-base font-bold text-white">Inventario</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">Ítems registrados ({{ $items->total() }})</p>
    </div>
    <a href="{{ route('mobile.inventario.nueva') }}"
       class="text-xs px-3 py-1.5 rounded-xl font-medium text-emerald-300"
       style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.25);">
        + Nuevo
    </a>
</div>

@if($items->isEmpty())
    <div class="card p-8 text-center">
        <p class="text-sm text-white mb-1">Sin ítems</p>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">No hay ítems en el inventario.</p>
    </div>
@else
    <div class="space-y-2">
        @foreach($items as $item)
        <div class="card p-4">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ $item->nombre }}</p>
                    <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">{{ $item->codigo }}</p>
                </div>
                <div class="text-right ml-3">
                    <p class="text-sm font-bold text-emerald-300">{{ number_format($item->stock_actual ?? 0, 2) }}</p>
                    <p class="text-xs" style="color: rgba(232,230,240,0.4);">{{ $item->measurementUnit->nombre ?? '—' }}</p>
                </div>
            </div>
            <div class="mt-2 flex items-center gap-2">
                <span class="text-xs px-2 py-0.5 rounded-full"
                      style="background: rgba(99,102,241,0.1); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.2);">
                    {{ $item->type ?? 'producto' }}
                </span>
                @if($item->sale_price)
                <span class="text-xs" style="color: rgba(232,230,240,0.4);">PVP ${{ number_format($item->sale_price, 2) }}</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $items->links() }}
    </div>
@endif

@endsection
