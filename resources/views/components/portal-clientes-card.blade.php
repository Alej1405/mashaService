{{--
    Portal de clientes — acceso al sitio público de la empresa.

    Mismo componente en todos los dashboards (vocabulario consistente entre paneles).
    Resuelve la URL del portal a partir del tenant activo (/tienda/{slug}/login);
    se puede sobreescribir con :url para contextos sin tenant (ej. panel Admin,
    donde el portal es por-empresa).

    Abre en pestaña nueva: es el sitio de cara al cliente, no una vista interna.
--}}
@props(['url' => null])

@php
    $slug = $url ? null : optional(\Filament\Facades\Filament::getTenant())->slug;
    $portalUrl = $url ?? ($slug ? url('/tienda/' . $slug . '/login') : null);
@endphp

@if ($portalUrl)
<a href="{{ $portalUrl }}" target="_blank" rel="noopener noreferrer" class="pcl-card">
    <span class="pcl-ico" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.999 2.999 0 0 0 4.5 0A2.993 2.993 0 0 0 18.75 9.75c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 0 0 3.75.615m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z" />
        </svg>
    </span>
    <span class="pcl-body">
        <span class="pcl-title">Portal de clientes</span>
        <span class="pcl-sub">Sitio público donde tus clientes ingresan con su cédula o RUC</span>
    </span>
    <span class="pcl-open">
        <span class="pcl-open-txt">Abrir</span>
        <svg class="pcl-open-ico" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
        </svg>
    </span>
</a>

<style>
    .pcl-card {
        display: flex;
        align-items: center;
        gap: 14px;
        margin: 0 0 20px;
        padding: 15px 18px;
        max-width: 1120px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        text-decoration: none;
        transition: border-color 160ms cubic-bezier(0.23, 1, 0.32, 1),
                    box-shadow 160ms cubic-bezier(0.23, 1, 0.32, 1),
                    transform 140ms cubic-bezier(0.23, 1, 0.32, 1);
    }
    .pcl-ico {
        flex: 0 0 auto;
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        color: #4338ca;
        background: #eef2ff;
        border: 1px solid #e0e7ff;
    }
    .pcl-ico svg { width: 22px; height: 22px; }
    .pcl-body { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
    .pcl-title { font-size: 0.95rem; font-weight: 600; color: #0f172a; line-height: 1.2; }
    .pcl-sub { font-size: 0.8rem; color: #64748b; line-height: 1.35; }
    .pcl-open {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 9999px;
        color: #4338ca;
        background: #eef2ff;
        border: 1px solid #e0e7ff;
        font-size: 0.78rem;
        font-weight: 600;
    }
    .pcl-open-ico {
        width: 15px;
        height: 15px;
        transition: transform 160ms cubic-bezier(0.23, 1, 0.32, 1);
    }

    @media (hover: hover) and (pointer: fine) {
        .pcl-card:hover {
            border-color: #c7d2fe;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
            transform: translateY(-2px);
        }
        .pcl-card:hover .pcl-open-ico { transform: translate(2px, -2px); }
    }
    .pcl-card:active { transform: translateY(0) scale(0.99); transition-duration: 90ms; }
    .pcl-card:focus-visible { outline: 2px solid #4f46e5; outline-offset: 2px; }

    @media (max-width: 560px) {
        .pcl-sub { display: none; }
    }
    @media (prefers-reduced-motion: reduce) {
        .pcl-card, .pcl-open-ico { transition: none; }
        .pcl-card:hover { transform: none; }
    }
</style>
@endif
