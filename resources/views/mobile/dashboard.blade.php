@extends('mobile.layout')
@section('title', $empresa->slug)

@section('content')

{{-- Header --}}
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        @if($empresa->logo_path)
            <img src="{{ Storage::disk('public')->url($empresa->logo_path) }}"
                 class="w-9 h-9 rounded-xl object-cover" alt="Logo">
        @else
            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold text-indigo-300"
                 style="background: rgba(79,70,229,0.2); border: 1px solid rgba(79,70,229,0.3);">
                {{ strtoupper(substr($empresa->slug, 0, 2)) }}
            </div>
        @endif
        <div>
            <p class="text-xs font-medium text-white">{{ auth()->user()->name }}</p>
            <p class="text-xs" style="color: rgba(232,230,240,0.4);">{{ $empresa->slug }}</p>
        </div>
    </div>
    <form method="POST" action="{{ route('mobile.logout') }}">
        @csrf
        <button type="submit"
                class="text-xs px-3 py-1.5 rounded-lg"
                style="background: rgba(255,255,255,0.06); color: rgba(232,230,240,0.5); border: 1px solid rgba(255,255,255,0.08);">
            Salir
        </button>
    </form>
</div>

{{-- Título --}}
<h2 class="text-lg font-bold text-white mb-1">Registros Rápidos</h2>
<p class="text-xs mb-6" style="color: rgba(232,230,240,0.4);">¿Qué deseas registrar hoy?</p>

{{-- Tarjetas de acceso rápido --}}
<div class="space-y-3">

    {{-- Inventario --}}
    <a href="{{ url('/enterprise/' . $empresa->slug . '/inventory-items/create') }}"
       class="card flex items-center gap-4 px-4 py-4 block active:scale-95 transition-transform">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.25);">
            <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">Inventario</p>
            <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">Agregar ítem al inventario</p>
        </div>
        <svg class="w-4 h-4 flex-shrink-0" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    {{-- Venta --}}
    <a href="{{ url('/enterprise/' . $empresa->slug . '/store-orders/create') }}"
       class="card flex items-center gap-4 px-4 py-4 block active:scale-95 transition-transform">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.25);">
            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">Venta</p>
            <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">Registrar nueva venta</p>
        </div>
        <svg class="w-4 h-4 flex-shrink-0" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    {{-- Orden de Producción --}}
    <a href="{{ url('/enterprise/' . $empresa->slug . '/production-orders/create') }}"
       class="card flex items-center gap-4 px-4 py-4 block active:scale-95 transition-transform">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.25);">
            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">Orden de Producción</p>
            <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">Registrar nueva orden</p>
        </div>
        <svg class="w-4 h-4 flex-shrink-0" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    {{-- Compra --}}
    <a href="{{ route('mobile.compra.ocr') }}"
       class="card flex items-center gap-4 px-4 py-4 block active:scale-95 transition-transform">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(236,72,153,0.15); border: 1px solid rgba(236,72,153,0.25);">
            <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21l-7-5-7 5V5a2 2 0 012-2h10a2 2 0 012 2v16z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">Compra</p>
            <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">Registrar nueva compra</p>
        </div>
        <svg class="w-4 h-4 flex-shrink-0" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

</div>

{{-- Validar compras (solo admin) --}}
@if(auth()->user()->hasRole('admin_empresa') || auth()->user()->hasRole('super_admin'))
@php $pendientes = \App\Models\Purchase::where('empresa_id', $empresa->id)->where('status','borrador')->count(); @endphp
@if($pendientes > 0)
<div class="mt-4">
    <a href="{{ route('mobile.compras.validar') }}"
       class="card flex items-center gap-4 px-4 py-4 block active:scale-95 transition-transform"
       style="border-color: rgba(245,158,11,0.35);">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3);">
            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-amber-300">Validar Compras</p>
            <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">
                {{ $pendientes }} compra{{ $pendientes > 1 ? 's' : '' }} pendiente{{ $pendientes > 1 ? 's' : '' }} de aprobación
            </p>
        </div>
        <svg class="w-4 h-4 flex-shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
</div>
@endif
@endif

{{-- Acceso al panel completo --}}
<div class="mt-6 pt-5" style="border-top: 1px solid rgba(255,255,255,0.08);">
    <p class="text-xs text-center mb-3" style="color: rgba(232,230,240,0.3);">
        Para gestión completa usa el panel en computadora
    </p>
    <a href="{{ url('/enterprise/' . $empresa->slug) }}"
       class="block text-center text-xs py-2.5 rounded-xl font-medium"
       style="background: rgba(255,255,255,0.05); color: rgba(232,230,240,0.45); border: 1px solid rgba(255,255,255,0.08);">
        Ir al Panel Enterprise completo
    </a>
</div>

@endsection
