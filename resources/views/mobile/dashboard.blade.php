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
<h2 class="text-lg font-bold text-white mb-1">Panel Operativo</h2>
<p class="text-xs mb-4" style="color: rgba(232,230,240,0.4);">Registros rápidos y consultas</p>

{{-- Acceso rápido a listas --}}
<div class="grid grid-cols-3 gap-2 mb-6">
    <a href="{{ route('mobile.inventario.lista') }}"
       class="card p-3 text-center active:scale-95 transition-transform">
        <p class="text-xs font-semibold text-emerald-300">Inventario</p>
        <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.35);">Ver ítems</p>
    </a>
    <a href="{{ route('mobile.ventas.lista') }}"
       class="card p-3 text-center active:scale-95 transition-transform">
        <p class="text-xs font-semibold text-indigo-300">Ventas</p>
        <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.35);">Ver ventas</p>
    </a>
    <a href="{{ route('mobile.compras.lista') }}"
       class="card p-3 text-center active:scale-95 transition-transform">
        <p class="text-xs font-semibold text-pink-300">Compras</p>
        <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.35);">Ver compras</p>
    </a>
    <a href="{{ route('mobile.deudas.lista') }}"
       class="card p-3 text-center active:scale-95 transition-transform">
        <p class="text-xs font-semibold text-violet-300">Deudas</p>
        <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.35);">Ver deudas</p>
    </a>
    <a href="{{ route('mobile.produccion.lista') }}"
       class="card p-3 text-center active:scale-95 transition-transform">
        <p class="text-xs font-semibold text-amber-300">Producción</p>
        <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.35);">Ver órdenes</p>
    </a>
    <a href="{{ route('mobile.disenos-producto.lista') }}"
       class="card p-3 text-center active:scale-95 transition-transform">
        <p class="text-xs font-semibold text-teal-300">Diseños</p>
        <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.35);">Ver diseños</p>
    </a>
</div>

<p class="text-xs font-semibold uppercase tracking-wider mb-3" style="color: rgba(232,230,240,0.35);">Registrar</p>

{{-- Tarjetas de acceso rápido --}}
<div class="space-y-3">

    {{-- Inventario --}}
    <a href="{{ route('mobile.inventario.nueva') }}"
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
    <a href="{{ route('mobile.venta.nueva') }}"
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
    <a href="{{ route('mobile.produccion.nueva') }}"
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

    {{-- Almacenes --}}
    <a href="{{ route('mobile.almacenes.index') }}"
       class="card flex items-center gap-4 px-4 py-4 block active:scale-95 transition-transform">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.25);">
            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-white">Almacenes</p>
            <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">Ver, crear y modificar almacenes</p>
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

    {{-- Deuda --}}
    <a href="{{ route('mobile.deuda.nueva') }}"
       class="card flex items-center gap-4 px-4 py-4 block active:scale-95 transition-transform">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.25);">
            <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">Deuda / Préstamo</p>
            <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">Registrar nueva deuda</p>
        </div>
        <svg class="w-4 h-4 flex-shrink-0" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    {{-- Diseño de Producto --}}
    <a href="{{ route('mobile.diseno-producto.nuevo') }}"
       class="card flex items-center gap-4 px-4 py-4 block active:scale-95 transition-transform">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(20,184,166,0.15); border: 1px solid rgba(20,184,166,0.25);">
            <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">Diseño de Producto</p>
            <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">Registrar info general y fórmula</p>
        </div>
        <svg class="w-4 h-4 flex-shrink-0" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    {{-- Logística --}}
    <a href="{{ route('mobile.logistica.index') }}"
       class="card flex items-center gap-4 px-4 py-4 block active:scale-95 transition-transform">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(6,182,212,0.15); border: 1px solid rgba(6,182,212,0.25);">
            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">Logística</p>
            <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">Cargas y embarques</p>
        </div>
        <svg class="w-4 h-4 flex-shrink-0" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    {{-- Cliente --}}
    <a href="{{ route('mobile.cliente.nuevo') }}"
       class="card flex items-center gap-4 px-4 py-4 block active:scale-95 transition-transform">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.25);">
            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">Nuevo Cliente</p>
            <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">Registrar acceso al portal</p>
        </div>
        <svg class="w-4 h-4 flex-shrink-0" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    {{-- Ecommerce --}}
    <a href="{{ route('mobile.ecommerce.index') }}"
       class="card flex items-center gap-4 px-4 py-4 block active:scale-95 transition-transform">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.25);">
            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">Ecommerce & CMS</p>
            <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">Tienda online y contenido web</p>
        </div>
        <svg class="w-4 h-4 flex-shrink-0" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

</div>

{{-- Validar compras y deudas (solo admin) --}}
@if(auth()->user()->hasRole('admin_empresa') || auth()->user()->hasRole('super_admin'))
@php
    $pendientesCompras = \App\Models\Purchase::where('empresa_id', $empresa->id)->where('status','borrador')->count();
    $pendientesDeudas  = \App\Models\Debt::where('empresa_id', $empresa->id)->where('estado','borrador')->count();
@endphp

@if($pendientesCompras > 0)
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
                {{ $pendientesCompras }} compra{{ $pendientesCompras > 1 ? 's' : '' }} pendiente{{ $pendientesCompras > 1 ? 's' : '' }} de aprobación
            </p>
        </div>
        <svg class="w-4 h-4 flex-shrink-0 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
</div>
@endif

@if($pendientesDeudas > 0)
<div class="mt-3">
    <a href="{{ route('mobile.deudas.validar') }}"
       class="card flex items-center gap-4 px-4 py-4 block active:scale-95 transition-transform"
       style="border-color: rgba(139,92,246,0.35);">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.3);">
            <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-violet-300">Validar Deudas</p>
            <p class="text-xs mt-0.5" style="color: rgba(232,230,240,0.45);">
                {{ $pendientesDeudas }} deuda{{ $pendientesDeudas > 1 ? 's' : '' }} pendiente{{ $pendientesDeudas > 1 ? 's' : '' }} de activación
            </p>
        </div>
        <svg class="w-4 h-4 flex-shrink-0 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
</div>
@endif

@endif

@endsection
