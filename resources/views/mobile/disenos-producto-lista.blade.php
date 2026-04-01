@extends('mobile.layout')
@section('title', 'Diseños de Producto')

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
        <h2 class="text-base font-bold text-white">Diseños de Producto</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">{{ $disenos->total() }} registros</p>
    </div>
    <a href="{{ route('mobile.diseno-producto.nuevo') }}"
       class="text-xs px-3 py-1.5 rounded-xl font-medium text-teal-300"
       style="background: rgba(20,184,166,0.12); border: 1px solid rgba(20,184,166,0.25);">
        + Nuevo
    </a>
</div>

@if($disenos->isEmpty())
    <div class="card p-8 text-center">
        <p class="text-sm text-white mb-1">Sin diseños</p>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">No hay diseños de producto registrados.</p>
    </div>
@else
    <div class="space-y-2">
        @foreach($disenos as $diseno)
        <div class="card p-4">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-white truncate">{{ $diseno->nombre }}</p>
                    @if($diseno->propuesta_valor)
                    <p class="text-xs mt-0.5 line-clamp-1" style="color: rgba(232,230,240,0.45);">{{ $diseno->propuesta_valor }}</p>
                    @endif
                </div>
                <span class="ml-3 text-xs px-2 py-0.5 rounded-full flex-shrink-0"
                      style="background: rgba(20,184,166,0.1); color: #5eead4; border: 1px solid rgba(20,184,166,0.2);">
                    {{ $diseno->presentations->count() }} present.
                </span>
            </div>
            @if($diseno->presentations->isNotEmpty())
            <div class="mt-2 flex flex-wrap gap-1">
                @foreach($diseno->presentations->take(3) as $pres)
                <span class="text-xs px-1.5 py-0.5 rounded"
                      style="background: rgba(255,255,255,0.05); color: rgba(232,230,240,0.6);">
                    {{ $pres->nombre ?? 'Base' }}
                </span>
                @endforeach
                @if($diseno->presentations->count() > 3)
                <span class="text-xs" style="color: rgba(232,230,240,0.35);">+{{ $diseno->presentations->count() - 3 }} más</span>
                @endif
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <div class="mt-4">{{ $disenos->links() }}</div>
@endif

@endsection
