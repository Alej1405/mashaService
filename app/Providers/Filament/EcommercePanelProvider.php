<?php

namespace App\Providers\Filament;

use App\Models\Empresa;
use App\Http\Middleware\FilamentAuthenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Facades\Filament;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\HtmlString;

class EcommercePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('ecommerce')
            ->path('store')
            ->login(\App\Filament\Auth\Login::class)
            ->tenant(Empresa::class, slugAttribute: 'slug')
            ->tenantProfile(\App\Filament\Pages\Tenancy\EditEmpresaProfile::class)
            ->colors([
                'primary' => Color::Cyan,
                'gray'    => Color::Slate,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger'  => Color::Rose,
                'info'    => Color::Sky,
            ])
            ->darkMode(false)
            ->font('Sansation')
            ->brandName(fn (): string => (Filament::getTenant()?->name ?? 'Masha Store') . ' — Tienda')
            ->brandLogo(fn (): ?string => ($logo = Filament::getTenant()?->logo_path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($logo)
                ? asset('storage/' . ltrim($logo, '/'))
                : null)
            ->favicon(fn (): ?string => ($logo = Filament::getTenant()?->logo_path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($logo)
                ? asset('storage/' . ltrim($logo, '/'))
                : null)
            ->brandLogoHeight('2rem')
            ->renderHook(
                'panels::head.done',
                fn (): HtmlString => new HtmlString('
                    <link rel="stylesheet" href="' . asset('css/aura-glass.css') . '">
                    <link rel="stylesheet" href="' . asset('css/filament/app/theme.css') . '">
                '),
            )
            ->renderHook(
                'panels::body.start',
                fn (): string => view('filament.loading')->render(),
            )
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make('Catálogo'),
                \Filament\Navigation\NavigationGroup::make('Ventas'),
                \Filament\Navigation\NavigationGroup::make('Clientes'),
                \Filament\Navigation\NavigationGroup::make('Promociones'),
                \Filament\Navigation\NavigationGroup::make('Contratos'),
            ])
            ->discoverResources(
                in: app_path('Filament/Ecommerce/Resources'),
                for: 'App\\Filament\\Ecommerce\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Ecommerce/Pages'),
                for: 'App\\Filament\\Ecommerce\\Pages'
            )
            ->pages([
                \App\Filament\Ecommerce\Pages\EcommerceDashboard::class,
            ])
            ->userMenuItems(\App\Support\PanelAccess::menuItems('ecommerce'))
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
                FilamentAuthenticate::class,
            ]);
    }
}
