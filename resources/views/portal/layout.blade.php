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
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root { --accent:#4f46e5; --accent-soft:#eef2ff; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; -webkit-font-smoothing: antialiased; }
        [x-cloak] { display:none !important; }
        .pv-in { animation: pv-in .32s cubic-bezier(0.23,1,0.32,1) both; }
        @keyframes pv-in { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform: translateY(0); } }
        .pv-nav { transition: background-color .15s ease, color .15s ease, box-shadow .15s ease; }
        .pv-nav:active { transform: scale(0.98); }
        @media (prefers-reduced-motion: reduce) { .pv-in, .pv-nav { animation: none; transition: none; } }
    </style>
</head>
<body class="min-h-screen text-slate-800" style="background:#f6f7f9">

@php
    $slug = $empresa->slug;
    $initial = mb_strtoupper(mb_substr($customer->nombre ?: ($customer->razon_social ?: '?'), 0, 1));

    $_tieneServicios = isset($tieneServicios)
        ? $tieneServicios
        : \App\Models\ServiceDesign::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('activo', true)
            ->exists();

    $links = [
        ['route' => 'portal.dashboard', 'label' => 'Inicio',      'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['route' => 'portal.orders',    'label' => 'Mis órdenes', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ['route' => 'portal.packages',  'label' => 'Mis cargas',  'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['route' => 'portal.profile',   'label' => 'Mi perfil',   'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
        ['route' => 'portal.companies', 'pattern' => 'portal.companies*', 'label' => 'Empresas', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
    ];

    if ($_tieneServicios) {
        array_splice($links, 2, 0, [[
            'route' => 'portal.services',
            'label' => 'Servicios',
            'icon'  => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        ]]);
    }

    if ($customer->publicado) {
        $links[] = ['route' => 'portal.web.edit', 'pattern' => 'portal.web*', 'label' => 'Mi web',
            'icon' => 'M21 12a9 9 0 11-18 0 9 9 0 0118 0z M3.6 9h16.8 M3.6 15h16.8 M12 3a15 15 0 000 18 M12 3a15 15 0 010 18'];
    }
    if ($customer->menu_activo) {
        $links[] = ['route' => 'portal.menu.index', 'pattern' => 'portal.menu*', 'label' => 'Mi menú',
            'icon' => 'M4 6h16M4 12h16M4 18h7'];
    }

    if ($customer->is_super_admin) {
        $links[] = ['route' => 'portal.customers', 'label' => 'Clientes', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'];
    }
@endphp

{{-- Barra superior --}}
<header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-200/80">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 flex items-center justify-between h-15" style="height:60px">
        <a href="{{ route('portal.dashboard', $slug) }}" class="flex items-center gap-2.5 min-w-0">
            @if($empresa->logo_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($empresa->logo_path) }}" alt="{{ $empresa->name }}" class="h-8 w-auto">
            @else
                <span class="font-bold text-slate-900 text-lg truncate">{{ $empresa->name }}</span>
            @endif
        </a>
        <div class="flex items-center gap-3">
            <div class="hidden sm:flex items-center gap-2.5">
                <span class="w-8 h-8 rounded-full grid place-items-center text-sm font-bold text-indigo-700 bg-indigo-50 border border-indigo-100">{{ $initial }}</span>
                <span class="text-sm font-medium text-slate-700 max-w-[10rem] truncate">{{ trim($customer->nombre.' '.($customer->apellido ?? '')) }}</span>
            </div>
            <form action="{{ route('portal.logout', $slug) }}" method="POST">
                @csrf
                <button type="submit" title="Cerrar sesión" class="pv-nav flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg px-2.5 py-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span class="hidden sm:inline">Salir</span>
                </button>
            </form>
        </div>
    </div>
</header>

<div class="max-w-6xl mx-auto px-4 sm:px-6 py-5 sm:py-7">

    {{-- Navegación móvil: pills desplazables --}}
    <div class="md:hidden mb-5 -mx-4 px-4">
        <div class="flex gap-2 overflow-x-auto pb-1">
            @foreach($links as $link)
                @php $active = request()->routeIs($link['pattern'] ?? $link['route']); @endphp
                <a href="{{ route($link['route'], $slug) }}"
                   class="pv-nav shrink-0 flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-sm font-medium
                          {{ $active ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/20' : 'bg-white text-slate-600 border border-slate-200' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/>
                    </svg>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="flex gap-7">

        {{-- Sidebar desktop --}}
        <aside class="w-52 shrink-0 hidden md:block">
            <nav class="space-y-1 sticky top-20">
                @foreach($links as $link)
                    @php $active = request()->routeIs($link['pattern'] ?? $link['route']); @endphp
                    <a href="{{ route($link['route'], $slug) }}"
                       class="pv-nav flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium
                              {{ $active
                                  ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/20'
                                  : 'text-slate-600 hover:bg-white hover:text-slate-900 hover:shadow-sm' }}">
                        <svg class="w-[18px] h-[18px] shrink-0 {{ $active ? '' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/>
                        </svg>
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>
        </aside>

        {{-- Contenido --}}
        <main class="flex-1 min-w-0 pv-in">
            @if(session('success'))
                <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800 flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('success') }}
                </div>
            @endif
            @yield('content')
        </main>

    </div>

</div>

{{-- Loader global del ERP: único para todo el portal, se dispara al enviar
     formularios o al navegar entre páginas. --}}
<div id="erp-loader" class="fixed inset-0 z-50 hidden items-center justify-center bg-white/75 backdrop-blur-sm" aria-hidden="true">
    <div class="flex flex-col items-center gap-3">
        <svg class="w-10 h-10 animate-spin text-indigo-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <span class="text-sm font-medium text-slate-600">Procesando…</span>
    </div>
</div>
<script>
    (function () {
        var el = document.getElementById('erp-loader');
        if (!el) return;
        var show = function () { el.classList.remove('hidden'); el.classList.add('flex'); el.setAttribute('aria-hidden', 'false'); };
        var hide = function () { el.classList.add('hidden'); el.classList.remove('flex'); el.setAttribute('aria-hidden', 'true'); };

        document.addEventListener('submit', function () { show(); }, true);

        document.addEventListener('click', function (e) {
            var a = e.target.closest && e.target.closest('a');
            if (!a) return;
            var href = a.getAttribute('href') || '';
            if (a.target === '_blank' || a.hasAttribute('download')) return;
            if (href === '' || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;
            show();
        }, true);

        window.addEventListener('pageshow', hide);
    })();
</script>

</body>
</html>
