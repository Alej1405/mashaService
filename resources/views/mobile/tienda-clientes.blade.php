@extends('mobile.layout')
@section('title', 'Clientes Tienda')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.ecommerce.index') }}" class="w-8 h-8 flex items-center justify-center rounded-xl"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div class="flex-1">
        <h2 class="text-base font-bold text-white">Clientes Tienda</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">{{ $clientes->total() }} clientes</p>
    </div>
</div>

@if($clientes->isEmpty())
    <div class="card p-8 text-center">
        <p class="text-sm text-white mb-1">Sin clientes</p>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">Los clientes se registran desde la tienda online.</p>
    </div>
@else
    <div class="space-y-2">
        @foreach($clientes as $cli)
        <div class="card p-4">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-white">{{ $cli->nombre }}</p>
                    <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">{{ $cli->email }}</p>
                    @if($cli->telefono)
                    <p class="text-xs" style="color: rgba(232,230,240,0.4);">{{ $cli->telefono }}</p>
                    @endif
                </div>
                <span class="text-xs" style="color: rgba(232,230,240,0.3);">{{ $cli->created_at?->format('d/m/Y') }}</span>
            </div>
        </div>
        @endforeach
    </div>
    <div class="mt-4">{{ $clientes->links() }}</div>
@endif

@endsection
