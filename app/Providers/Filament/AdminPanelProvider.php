<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\HtmlString;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Auth\Login::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->font('Sansation')
            ->darkMode(false)
            ->navigationGroups([
                NavigationGroup::make('Clientes'),
                NavigationGroup::make('Plataforma'),
                NavigationGroup::make('Monitoreo'),
                NavigationGroup::make('Sistema'),
            ])
            ->renderHook('panels::head.end', fn (): HtmlString => new HtmlString('<style>
                /* ── Utilidades de grid no incluidas en el build de Tailwind ── */
                .grid-cols-5{grid-template-columns:repeat(5,minmax(0,1fr))}
                .grid-cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}
                @media(min-width:1024px){
                    .lg\:grid-cols-5{grid-template-columns:repeat(5,minmax(0,1fr))}
                    .lg\:grid-cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}
                    .lg\:grid-cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}
                    .lg\:col-span-2{grid-column:span 2/span 2}
                }

                /* ── Sidebar: etiquetas de grupo discretas (impeccable: jerarquía por tamaño) ── */
                .fi-sidebar-group-label {
                    font-size: 10px !important;
                    font-weight: 700 !important;
                    letter-spacing: 0.08em !important;
                    text-transform: uppercase !important;
                    color: #94a3b8 !important;
                    padding-top: 6px !important;
                }

                /* ── Sidebar: transición de hover (Emil: ease-out fuerte, 120ms) ── */
                .fi-sidebar-item-button {
                    transition:
                        background-color 120ms cubic-bezier(0.23, 1, 0.32, 1),
                        transform 100ms ease-out !important;
                }

                /* ── Sidebar: ícono inactivo sutil para no competir con el contenido ── */
                .fi-sidebar-item-button:not(.fi-active) .fi-sidebar-item-icon {
                    color: #94a3b8 !important;
                }

                /* ── Sidebar: ítem activo — fondo amber-50, sin borde lateral (impeccable ban) ── */
                .fi-sidebar-item-button.fi-active {
                    background-color: #fffbeb !important;
                }

                /* ── Sidebar: ícono activo en amber-600 (color firma del sistema) ── */
                .fi-sidebar-item-button.fi-active .fi-sidebar-item-icon {
                    color: #d97706 !important;
                }

                /* ── Sidebar: label activo en slate-900, semibold (contraste claro) ── */
                .fi-sidebar-item-button.fi-active .fi-sidebar-item-label {
                    color: #0f172a !important;
                    font-weight: 600 !important;
                }

                /* ── Sidebar: hover solo en dispositivos con puntero fino (Emil: gate con media query) ── */
                @media (hover: hover) and (pointer: fine) {
                    .fi-sidebar-item-button:hover:not(.fi-active) {
                        background-color: #f8fafc !important;
                    }
                    .fi-sidebar-item-button:hover:not(.fi-active) .fi-sidebar-item-label {
                        color: #334155 !important;
                    }
                    .fi-sidebar-item-button:hover:not(.fi-active) .fi-sidebar-item-icon {
                        color: #64748b !important;
                    }

                    /* ── Sidebar: feedback táctil en :active (Emil: scale(0.97)) ── */
                    .fi-sidebar-item-button:active {
                        transform: scale(0.97) !important;
                    }
                }

                /* ── Sidebar: chevron gira suavemente al abrir/cerrar grupo ── */
                .fi-sidebar-group-collapse-button {
                    transition: transform 220ms cubic-bezier(0.23, 1, 0.32, 1) !important;
                }

                /* ── Sidebar: grupo — hover y press en el botón del encabezado ── */
                .fi-sidebar-group-button {
                    transition: background-color 120ms cubic-bezier(0.23, 1, 0.32, 1) !important;
                    border-radius: 8px !important;
                }
                @media (hover: hover) and (pointer: fine) {
                    .fi-sidebar-group-button:hover {
                        background-color: #f8fafc !important;
                    }
                    .fi-sidebar-group-button:active {
                        transform: scale(0.98) !important;
                        transition:
                            background-color 120ms cubic-bezier(0.23, 1, 0.32, 1),
                            transform 100ms ease-out !important;
                    }
                }

                /* ── Módulos de empresa: hover en fila (Emil: 120ms ease-out) ── */
                .fi-fo-field-wrp:has(.fi-fo-placeholder[id*="mod_card_"]) + * .fi-fo-toggle,
                [id*="mod_card_"] {
                    transition: opacity 120ms cubic-bezier(0.23, 1, 0.32, 1) !important;
                }

                /* ── prefers-reduced-motion: eliminar todas las transiciones ── */
                @media (prefers-reduced-motion: reduce) {
                    .fi-sidebar-item-button,
                    .fi-sidebar-group-button,
                    .fi-sidebar-group-collapse-button {
                        transition: none !important;
                    }
                    .fi-sidebar-item-button:active,
                    .fi-sidebar-group-button:active {
                        transform: none !important;
                    }
                }
            </style>'))
            ->renderHook('panels::body.end', fn (): HtmlString => new HtmlString('
<script>
document.addEventListener("alpine:initialized", function () {
    var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

    function setupAccordion() {
        var groups = Array.from(
            document.querySelectorAll(".fi-sidebar-nav .fi-sidebar-group[data-group-label]")
        );
        if (!groups.length) return;

        groups.forEach(function (group) {
            var btn = group.querySelector(":scope > .fi-sidebar-group-button");
            if (!btn || btn._accordionReady) return;
            btn._accordionReady = true;

            btn.addEventListener("click", function () {
                requestAnimationFrame(function () {
                    var store = window.Alpine && window.Alpine.store("sidebar");
                    if (!store) return;

                    /* Garantizar que collapsedGroups sea un array (puede iniciar como null) */
                    if (!Array.isArray(store.collapsedGroups)) {
                        store.collapsedGroups = [];
                    }

                    var clickedLabel = group.getAttribute("data-group-label");

                    /* Solo actuar si el grupo clickeado quedó ABIERTO */
                    if (store.groupIsCollapsed(clickedLabel)) return;

                    /* Acordeón: colapsar todos los demás grupos */
                    groups.forEach(function (other) {
                        var otherLabel = other.getAttribute("data-group-label");
                        if (otherLabel === clickedLabel) return;
                        store.collapseGroup(otherLabel);
                    });

                    /* Stagger de ítems con WAAPI (respeta prefers-reduced-motion) */
                    if (!reduce) {
                        var items = Array.from(
                            group.querySelectorAll(".fi-sidebar-group-items .fi-sidebar-item")
                        );
                        items.forEach(function (item, i) {
                            item.animate(
                                [
                                    { opacity: 0, transform: "translateX(-8px)" },
                                    { opacity: 1, transform: "translateX(0)" }
                                ],
                                {
                                    duration: 200,
                                    delay: 80 + i * 45,
                                    easing: "cubic-bezier(0.23, 1, 0.32, 1)",
                                    fill: "both"
                                }
                            );
                        });
                    }
                });
            });
        });
    }

    setupAccordion();

    /* Reintento si el sidebar carga tarde (Livewire SPA navigation) */
    var obs = new MutationObserver(function () { setupAccordion(); });
    obs.observe(document.body, { childList: true, subtree: true });
    setTimeout(function () { obs.disconnect(); }, 5000);
});
</script>
'))
            ->brandLogo(asset('logo.png'))
            ->brandName('Masha Corp S.A.S.')
            ->favicon(asset('logo.png'))
            ->broadcasting()
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([])
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
