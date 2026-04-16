@extends('mobile.layout')
@section('title', 'Logística')

@section('content')

{{-- Header --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.index') }}"
       class="w-8 h-8 flex items-center justify-center rounded-xl"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div>
        <h2 class="text-base font-bold text-white">Logística</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">Cargas y embarques</p>
    </div>
</div>

{{-- Resumen rápido --}}
<div class="grid grid-cols-2 gap-3 mb-6">
    <a href="{{ route('mobile.logistica.cargas.lista') }}"
       class="card p-4 active:scale-95 transition-transform text-center">
        <p class="text-2xl font-bold text-cyan-300">{{ $totalCargas }}</p>
        <p class="text-xs mt-1" style="color: rgba(232,230,240,0.45);">Cargas</p>
    </a>
    <a href="{{ route('mobile.logistica.embarques.lista') }}"
       class="card p-4 active:scale-95 transition-transform text-center">
        <p class="text-2xl font-bold text-indigo-300">{{ $totalEmbarques }}</p>
        <p class="text-xs mt-1" style="color: rgba(232,230,240,0.45);">Embarques</p>
    </a>
</div>

<p class="text-xs font-semibold uppercase tracking-wider mb-3" style="color: rgba(232,230,240,0.35);">Registrar</p>

<div class="space-y-3 mb-6">

    {{-- Nueva carga --}}
    <a href="{{ route('mobile.logistica.carga.nueva') }}"
       class="card flex items-center gap-4 px-4 py-4 block active:scale-95 transition-transform">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(6,182,212,0.15); border: 1px solid rgba(6,182,212,0.25);">
            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">Nueva Carga</p>
            <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">Registrar paquete recibido</p>
        </div>
        <svg class="w-4 h-4 flex-shrink-0" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    {{-- Nuevo embarque --}}
    <a href="{{ route('mobile.logistica.embarque.nuevo') }}"
       class="card flex items-center gap-4 px-4 py-4 block active:scale-95 transition-transform">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.25);">
            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">Nuevo Embarque</p>
            <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">Crear y asignar cargas</p>
        </div>
        <svg class="w-4 h-4 flex-shrink-0" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
</div>

<p class="text-xs font-semibold uppercase tracking-wider mb-3" style="color: rgba(232,230,240,0.35);">Consultar y actualizar</p>

<div class="space-y-3">

    {{-- Lista de cargas --}}
    <a href="{{ route('mobile.logistica.cargas.lista') }}"
       class="card flex items-center gap-4 px-4 py-4 block active:scale-95 transition-transform">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(6,182,212,0.10); border: 1px solid rgba(6,182,212,0.20);">
            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">Ver cargas</p>
            <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">Listar y actualizar estados</p>
        </div>
        <svg class="w-4 h-4 flex-shrink-0" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    {{-- Lista de embarques --}}
    <a href="{{ route('mobile.logistica.embarques.lista') }}"
       class="card flex items-center gap-4 px-4 py-4 block active:scale-95 transition-transform">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(99,102,241,0.10); border: 1px solid rgba(99,102,241,0.20);">
            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">Ver embarques</p>
            <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">Historial de embarques</p>
        </div>
        <svg class="w-4 h-4 flex-shrink-0" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
</div>

@endsection
