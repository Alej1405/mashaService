@extends('mobile.layout')
@section('title', 'Órdenes de Producción')

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
        <h2 class="text-base font-bold text-white">Producción</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">{{ $ordenes->total() }} órdenes</p>
    </div>
    <a href="{{ route('mobile.produccion.nueva') }}"
       class="text-xs px-3 py-1.5 rounded-xl font-medium text-amber-300"
       style="background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.25);">
        + Nueva
    </a>
</div>

@if($ordenes->isEmpty())
    <div class="card p-8 text-center">
        <p class="text-sm text-white mb-1">Sin órdenes</p>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">No hay órdenes de producción.</p>
    </div>
@else
    <div class="space-y-2">
        @foreach($ordenes as $orden)
        @php
            $estadoColor = match($orden->estado) {
                'completado' => ['bg'=>'rgba(16,185,129,0.1)',  'color'=>'#6ee7b7',  'border'=>'rgba(16,185,129,0.25)'],
                'en_proceso' => ['bg'=>'rgba(99,102,241,0.1)',  'color'=>'#a5b4fc',  'border'=>'rgba(99,102,241,0.25)'],
                'borrador'   => ['bg'=>'rgba(245,158,11,0.1)',  'color'=>'#fcd34d',  'border'=>'rgba(245,158,11,0.25)'],
                default      => ['bg'=>'rgba(255,255,255,0.05)','color'=>'#cbd5e1',  'border'=>'rgba(255,255,255,0.1)'],
            };
        @endphp
        <div class="card p-4">
            <div class="flex items-start justify-between mb-1">
                <div>
                    <p class="text-sm font-bold text-white">{{ $orden->referencia }}</p>
                    <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">
                        {{ $orden->productPresentation?->productDesign?->nombre ?? '—' }}
                        · {{ \Carbon\Carbon::parse($orden->fecha)->format('d/m/Y') }}
                    </p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full"
                      style="background: {{ $estadoColor['bg'] }}; color: {{ $estadoColor['color'] }}; border: 1px solid {{ $estadoColor['border'] }};">
                    {{ ucfirst(str_replace('_', ' ', $orden->estado)) }}
                </span>
            </div>
            <div class="flex items-center justify-between text-xs mt-2" style="color: rgba(232,230,240,0.5);">
                <span>Cantidad: <span class="text-white">{{ number_format($orden->cantidad_producida, 2) }}</span></span>
                <span>Costo: <span class="text-white">${{ number_format($orden->costo_total, 2) }}</span></span>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4">{{ $ordenes->links() }}</div>
@endif

@endsection
