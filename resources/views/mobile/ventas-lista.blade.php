@extends('mobile.layout')
@section('title', 'Ventas')

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
        <h2 class="text-base font-bold text-white">Ventas</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">{{ $ventas->total() }} registros</p>
    </div>
    <a href="{{ route('mobile.venta.nueva') }}"
       class="text-xs px-3 py-1.5 rounded-xl font-medium text-indigo-300"
       style="background: rgba(99,102,241,0.12); border: 1px solid rgba(99,102,241,0.25);">
        + Nueva
    </a>
</div>

@if($ventas->isEmpty())
    <div class="card p-8 text-center">
        <p class="text-sm text-white mb-1">Sin ventas</p>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">No hay ventas registradas.</p>
    </div>
@else
    <div class="space-y-2">
        @foreach($ventas as $venta)
        @php
            $colorMap = ['confirmada'=>'emerald','borrador'=>'amber','anulada'=>'red'];
            $color = $colorMap[$venta->estado] ?? 'indigo';
        @endphp
        <div class="card p-4">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <p class="text-sm font-bold text-white">{{ $venta->referencia }}</p>
                    <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">
                        {{ $venta->customer->nombre ?? 'Sin cliente' }} · {{ $venta->fecha?->format('d/m/Y') }}
                    </p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full font-medium text-{{ $color }}-300"
                      style="background: rgba(var(--tw-{{ $color }}-rgb, 16,185,129),0.1); border: 1px solid currentColor; opacity: 0.8;">
                    {{ ucfirst($venta->estado) }}
                </span>
            </div>
            <div class="flex items-center justify-between text-xs" style="color: rgba(232,230,240,0.5);">
                <span>{{ ucfirst($venta->tipo_venta ?? '—') }}</span>
                <span class="text-white font-bold text-sm">${{ number_format($venta->total, 2) }}</span>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4">{{ $ventas->links() }}</div>
@endif

@endsection
