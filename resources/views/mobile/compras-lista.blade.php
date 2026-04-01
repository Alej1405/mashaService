@extends('mobile.layout')
@section('title', 'Compras')

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
        <h2 class="text-base font-bold text-white">Compras</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">{{ $compras->total() }} registros</p>
    </div>
    <a href="{{ route('mobile.compra.ocr') }}"
       class="text-xs px-3 py-1.5 rounded-xl font-medium text-pink-300"
       style="background: rgba(236,72,153,0.12); border: 1px solid rgba(236,72,153,0.25);">
        + Nueva
    </a>
</div>

@if($compras->isEmpty())
    <div class="card p-8 text-center">
        <p class="text-sm text-white mb-1">Sin compras</p>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">No hay compras registradas.</p>
    </div>
@else
    <div class="space-y-2">
        @foreach($compras as $compra)
        <div class="card p-4">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <p class="text-sm font-bold text-white">{{ $compra->number ?? $compra->numero_factura ?? 'COM-' . $compra->id }}</p>
                    <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">
                        {{ $compra->supplier->nombre ?? 'Sin proveedor' }} · {{ $compra->date?->format('d/m/Y') }}
                    </p>
                </div>
                @php $esConfirmada = $compra->status === 'confirmada' || $compra->status === 'confirmado'; @endphp
                <span class="text-xs px-2 py-0.5 rounded-full"
                      style="background: {{ $esConfirmada ? 'rgba(16,185,129,0.1)' : 'rgba(245,158,11,0.1)' }};
                             color: {{ $esConfirmada ? '#6ee7b7' : '#fcd34d' }};
                             border: 1px solid {{ $esConfirmada ? 'rgba(16,185,129,0.25)' : 'rgba(245,158,11,0.25)' }};">
                    {{ ucfirst($compra->status) }}
                </span>
            </div>
            <div class="text-right">
                <span class="text-sm font-bold text-white">${{ number_format($compra->total, 2) }}</span>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4">{{ $compras->links() }}</div>
@endif

@endsection
