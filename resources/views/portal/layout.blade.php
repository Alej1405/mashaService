<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Portal' }} — {{ $empresa->name }}</title>
    @if($empresa->logo_path)
        <link rel="icon" type="image/png" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path) }}">
        <link rel="apple-touch-icon" href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path) }}">
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-50 min-h-screen">

{{-- Navbar --}}
<nav class="bg-white border-b border-gray-200 sticky top-0 z-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 flex items-center justify-between h-14">
        <div class="flex items-center gap-3">
            @if($empresa->logo_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path) }}" alt="{{ $empresa->name }}" class="h-8 w-auto">
            @else
                <span class="font-bold text-gray-800 text-lg">{{ $empresa->name }}</span>
            @endif
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600 hidden sm:block">{{ $customer->nombre }} {{ $customer->apellido }}</span>
            <form action="{{ route('portal.logout', $empresa->slug) }}" method="POST">
                @csrf
                <button type="submit" class="text-sm text-gray-500 hover:text-gray-800 transition">Cerrar sesión</button>
            </form>
        </div>
    </div>
</nav>

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-4 sm:py-6">

    @php
        $slug = $empresa->slug;
        $links = [
            ['route' => 'portal.dashboard', 'label' => 'Inicio',      'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['route' => 'portal.orders',    'label' => 'Mis órdenes', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
            ['route' => 'portal.services',  'label' => 'Servicios',   'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
            ['route' => 'portal.packages',  'label' => 'Mis cargas',  'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
            ['route' => 'portal.profile',   'label' => 'Mi perfil',   'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        ];
        if ($customer->is_super_admin) {
            $links[] = ['route' => 'portal.customers', 'label' => 'Clientes', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'];
        }
    @endphp

    {{-- Navegación móvil: barra superior de tabs --}}
    <div class="md:hidden mb-4">
        <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
            @foreach($links as $link)
                @php $active = request()->routeIs($link['route']); @endphp
                <a href="{{ route($link['route'], $slug) }}"
                   class="shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium transition
                          {{ $active ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/>
                    </svg>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Layout principal: sidebar (desktop) + contenido --}}
    <div class="flex gap-6">

        {{-- Sidebar desktop --}}
        <aside class="w-48 shrink-0 hidden md:block">
            <nav class="space-y-1 sticky top-20">
                @foreach($links as $link)
                    @php $active = request()->routeIs($link['route']); @endphp
                    <a href="{{ route($link['route'], $slug) }}"
                       class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm transition
                              {{ $active ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-800' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/>
                        </svg>
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>
        </aside>

        {{-- Contenido --}}
        <main class="flex-1 min-w-0">
            @if(session('success'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif
            @yield('content')
        </main>

    </div>

</div>

</body>
</html>
