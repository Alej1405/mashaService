@php
    $empresa = \Filament\Facades\Filament::getTenant();
    if (! $empresa) return;

    $ruta = request()->path();
    $destinos = [
        ['Inicio', \App\Filament\Contabilidad\Pages\Dashboard::getUrl(tenant: $empresa), str_ends_with($ruta, $empresa->slug),
         'M2.25 12l8.954-8.955a1.5 1.5 0 012.122 0L22.28 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75'],
        ['Asientos', \App\Filament\Contabilidad\Resources\AsientoResource::getUrl(tenant: $empresa), str_contains($ruta, 'asientos'),
         'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25'],
        ['Declarar', \App\Filament\Contabilidad\Pages\Declaraciones::getUrl(tenant: $empresa), str_contains($ruta, 'declaraciones'),
         'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'],
        ['Cierre', \App\Filament\Contabilidad\Pages\CierreEjercicio::getUrl(tenant: $empresa), str_contains($ruta, 'cierre'),
         'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z'],
    ];
@endphp

{{-- En celular y tablet la navegación va abajo: el sidebar desaparece. --}}
<style>
    .ct-nav { display:none; }

    @media (max-width:1023px) {
        .fi-sidebar, .fi-topbar-open-sidebar-btn, .fi-sidebar-close-overlay,
        .fi-sidebar-close-btn, .fi-topbar-close-sidebar-btn { display:none !important; }
        .fi-main-ctn { margin-inline-start:0 !important; }
        .fi-main { padding-bottom:calc(76px + env(safe-area-inset-bottom)) !important; }
        .fi-btn, .fi-ac-btn-action, .fi-ta-actions .fi-icon-btn, .fi-pagination-item { min-height:44px !important; }

        .ct-nav {
            position:fixed; left:0; right:0; bottom:0; z-index:40;
            display:grid; grid-template-columns:repeat(4,1fr);
            padding:6px 6px calc(6px + env(safe-area-inset-bottom));
            background:rgba(255,255,255,.94); backdrop-filter:blur(14px);
            border-top:1px solid #e2e8f0; box-shadow:0 -6px 20px -12px rgba(15,23,42,.35);
            font-family:'Sansation', ui-sans-serif, system-ui, sans-serif;
        }
        .ct-nav a { display:flex; flex-direction:column; align-items:center; gap:3px;
                    padding:8px 4px 6px; border-radius:12px; text-decoration:none;
                    font-size:11px; font-weight:700; color:#64748b; }
        .ct-nav a:active { background:#f1f5f9; }
        .ct-nav .es-activo { color:#4f46e5; }
        .ct-nav svg { width:22px; height:22px; }
    }
</style>

<nav class="ct-nav" aria-label="Navegación de contabilidad">
    @foreach ($destinos as [$etiqueta, $url, $activo, $glifo])
        <a href="{{ $url }}" class="{{ $activo ? 'es-activo' : '' }}">
            <svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $glifo }}"/>
            </svg>
            {{ $etiqueta }}
        </a>
    @endforeach
</nav>
