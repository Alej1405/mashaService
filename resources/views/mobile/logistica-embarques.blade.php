@extends('mobile.layout')
@section('title', 'Embarques')

@section('content')

{{-- Header --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.logistica.index') }}"
       class="w-8 h-8 flex items-center justify-center rounded-xl"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div class="flex-1">
        <h2 class="text-base font-bold text-white">Embarques</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">{{ $embarques->total() }} registrados</p>
    </div>
    <a href="{{ route('mobile.logistica.embarque.nuevo') }}"
       class="px-3 py-1.5 rounded-xl text-xs font-semibold"
       style="background: rgba(99,102,241,0.2); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.3);">
        + Nuevo
    </a>
</div>

@if($embarques->isEmpty())
    <div class="card px-6 py-12 text-center">
        <p class="text-sm" style="color: rgba(232,230,240,0.4);">No hay embarques registrados.</p>
    </div>
@else
    <div class="space-y-3">
        @foreach($embarques as $embarque)
        @php
            $info  = \App\Models\LogisticsShipment::ESTADOS[$embarque->estado] ?? [];
            $label = $info['label'] ?? $embarque->estado;
            $color = $info['color'] ?? '#6b7280';
            $tipo  = \App\Models\LogisticsShipment::TIPOS[$embarque->tipo] ?? $embarque->tipo;
        @endphp
        <div class="card px-4 py-3">
            <div class="flex items-start justify-between gap-2">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white font-mono truncate">
                        {{ $embarque->numero_embarque }}
                    </p>
                    <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.4);">{{ $tipo }}</p>
                    @if($embarque->numero_guia_aerea)
                        <p class="text-xs font-mono mt-0.5" style="color: rgba(232,230,240,0.3);">{{ $embarque->numero_guia_aerea }}</p>
                    @endif
                </div>
                <span class="shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full"
                      style="background-color:{{ $color }}22; color:{{ $color }}; border:1px solid {{ $color }}44;">
                    {{ $label }}
                </span>
            </div>
            <div class="flex items-center justify-between mt-3">
                <p class="text-xs" style="color: rgba(232,230,240,0.3);">
                    {{ $embarque->created_at->format('d/m/Y') }}
                    @if($embarque->packages_count > 0)
                        · <span style="color: rgba(232,230,240,0.45);">{{ $embarque->packages_count }} carga{{ $embarque->packages_count !== 1 ? 's' : '' }}</span>
                    @endif
                </p>
                @if($embarque->fecha_llegada_ecuador)
                    <p class="text-xs" style="color: rgba(232,230,240,0.3);">
                        Llegada {{ \Carbon\Carbon::parse($embarque->fecha_llegada_ecuador)->format('d/m/Y') }}
                    </p>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Paginación --}}
    @if($embarques->hasPages())
    <div class="flex justify-between items-center mt-6 gap-2">
        @if($embarques->onFirstPage())
            <span class="text-xs px-3 py-1.5 rounded-xl opacity-30"
                  style="background:rgba(255,255,255,0.05); color:rgba(232,230,240,0.4); border:1px solid rgba(255,255,255,0.08);">← Anterior</span>
        @else
            <a href="{{ $embarques->previousPageUrl() }}"
               class="text-xs px-3 py-1.5 rounded-xl"
               style="background:rgba(255,255,255,0.06); color:rgba(232,230,240,0.6); border:1px solid rgba(255,255,255,0.1);">← Anterior</a>
        @endif
        <span class="text-xs" style="color:rgba(232,230,240,0.35);">Página {{ $embarques->currentPage() }} de {{ $embarques->lastPage() }}</span>
        @if($embarques->hasMorePages())
            <a href="{{ $embarques->nextPageUrl() }}"
               class="text-xs px-3 py-1.5 rounded-xl"
               style="background:rgba(255,255,255,0.06); color:rgba(232,230,240,0.6); border:1px solid rgba(255,255,255,0.1);">Siguiente →</a>
        @else
            <span class="text-xs px-3 py-1.5 rounded-xl opacity-30"
                  style="background:rgba(255,255,255,0.05); color:rgba(232,230,240,0.4); border:1px solid rgba(255,255,255,0.08);">Siguiente →</span>
        @endif
    </div>
    @endif
@endif

@endsection
