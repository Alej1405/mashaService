@extends('mobile.layout')
@section('title', 'Ecommerce')

@section('content')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('mobile.index') }}"
       class="w-8 h-8 flex items-center justify-center rounded-xl"
       style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <div>
        <h2 class="text-base font-bold text-white">Ecommerce & CMS</h2>
        <p class="text-xs" style="color: rgba(232,230,240,0.4);">Tienda y contenido web</p>
    </div>
</div>

{{-- Tienda --}}
<p class="text-xs font-semibold text-indigo-300 uppercase tracking-wider mb-3">Tienda Online</p>
<div class="space-y-2 mb-6">

    <a href="{{ route('mobile.tienda.productos.index') }}"
       class="card flex items-center gap-4 px-4 py-3 block active:scale-95 transition-transform">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.25);">
            <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">Productos</p>
            <p class="text-xs" style="color: rgba(232,230,240,0.45);">Ver, crear y editar productos</p>
        </div>
        <svg class="w-4 h-4" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    <a href="{{ route('mobile.tienda.pedidos.index') }}"
       class="card flex items-center gap-4 px-4 py-3 block active:scale-95 transition-transform">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.25);">
            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">Pedidos</p>
            <p class="text-xs" style="color: rgba(232,230,240,0.45);">Ver y gestionar pedidos</p>
        </div>
        <svg class="w-4 h-4" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    <a href="{{ route('mobile.tienda.categorias.index') }}"
       class="card flex items-center gap-4 px-4 py-3 block active:scale-95 transition-transform">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.25);">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">Categorías</p>
            <p class="text-xs" style="color: rgba(232,230,240,0.45);">Organizar productos por categoría</p>
        </div>
        <svg class="w-4 h-4" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>

    <a href="{{ route('mobile.tienda.clientes.index') }}"
       class="card flex items-center gap-4 px-4 py-3 block active:scale-95 transition-transform">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba(139,92,246,0.15); border: 1px solid rgba(139,92,246,0.25);">
            <svg class="w-4 h-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">Clientes Tienda</p>
            <p class="text-xs" style="color: rgba(232,230,240,0.45);">Ver clientes registrados</p>
        </div>
        <svg class="w-4 h-4" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
</div>

{{-- CMS --}}
<p class="text-xs font-semibold text-teal-300 uppercase tracking-wider mb-3">Contenido Web (CMS)</p>
<div class="space-y-2">

    @php
    $cms = [
        ['label' => 'Hero / Banner', 'sub' => 'Título y llamada a la acción', 'route' => 'cms.hero', 'color' => '99,102,241'],
        ['label' => 'Nosotros', 'sub' => 'Descripción de la empresa', 'route' => 'cms.about', 'color' => '20,184,166'],
        ['label' => 'Contacto', 'sub' => 'Dirección, teléfono, redes', 'route' => 'cms.contacto', 'color' => '16,185,129'],
        ['label' => 'Servicios', 'sub' => 'Lista de servicios ofrecidos', 'route' => 'cms.servicios.index', 'color' => '245,158,11'],
        ['label' => 'Equipo', 'sub' => 'Miembros del equipo', 'route' => 'cms.equipo.index', 'color' => '236,72,153'],
        ['label' => 'Testimonios', 'sub' => 'Reseñas de clientes', 'route' => 'cms.testimonios.index', 'color' => '139,92,246'],
        ['label' => 'FAQs', 'sub' => 'Preguntas frecuentes', 'route' => 'cms.faqs.index', 'color' => '59,130,246'],
        ['label' => 'Blog / Posts', 'sub' => 'Artículos y noticias', 'route' => 'cms.posts.index', 'color' => '234,179,8'],
        ['label' => 'Logos de Clientes', 'sub' => 'Marcas asociadas', 'route' => 'cms.logos.index', 'color' => '107,114,128'],
    ];
    @endphp

    @foreach($cms as $item)
    <a href="{{ route('mobile.' . $item['route']) }}"
       class="card flex items-center gap-4 px-4 py-3 block active:scale-95 transition-transform">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: rgba({{ $item['color'] }},0.15); border: 1px solid rgba({{ $item['color'] }},0.25);">
            <svg class="w-4 h-4" style="color: rgba({{ $item['color'] }},0.9);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
        </div>
        <div class="flex-1">
            <p class="text-sm font-semibold text-white">{{ $item['label'] }}</p>
            <p class="text-xs" style="color: rgba(232,230,240,0.45);">{{ $item['sub'] }}</p>
        </div>
        <svg class="w-4 h-4" style="color: rgba(232,230,240,0.25);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
    @endforeach

</div>

@endsection
