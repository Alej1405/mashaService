<?php

namespace App\Providers\Filament;

use App\Models\Empresa;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Panel de Operaciones.
 *
 * Quien trabaja en bodega, no quien lleva la contabilidad. Por eso la
 * navegación es corta: lo que hay, dónde está y qué se movió. Nada de
 * asientos ni de informes financieros.
 *
 * El rol `inventario` ya existía en la base; este panel es la pantalla que le
 * faltaba.
 */
class OperacionesPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('operaciones')
            ->path('operaciones')
            ->login(\App\Filament\Auth\LoginUsuarios::class)
            ->tenant(Empresa::class, slugAttribute: 'slug')
            ->colors([
                'primary' => Color::Indigo,
                'gray'    => Color::Slate,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger'  => Color::Rose,
            ])
            ->font('Sansation')
            ->brandName(fn (): string => Filament::getTenant()?->name ?? 'Operaciones')
            ->brandLogo(fn (): ?string => ($logo = Filament::getTenant()?->logo_path)
                && \Illuminate\Support\Facades\Storage::disk('public')->exists($logo)
                    ? asset('storage/' . ltrim($logo, '/'))
                    : null)
            ->brandLogoHeight('2rem')
            ->darkMode(false)
            // Sin aura-glass.css ni el tema del panel App: ese CSS pinta el fondo
            // oscuro con !important y este panel es claro. Juntos dejaban el texto
            // gris sobre azul marino y el contenido corrido fuera del viewport.
            // Al sacar el tema del panel App se fue con él el aire vertical del
            // contenido: el contenedor traía 32 px a los lados y cero arriba y
            // abajo, así que todo quedaba pegado al borde.
            ->renderHook(
                'panels::head.end',
                fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                    '<style>.fi-main{padding-top:2rem;padding-bottom:2.5rem}'
                    . '@media(max-width:640px){.fi-main{padding-top:1.25rem;padding-bottom:1.75rem}}</style>'
                ),
            )
            // En celular y tablet esto no es una web con menú lateral: es una app.
            // El sidebar desaparece y la navegación vive abajo, al alcance del
            // pulgar. En escritorio no cambia nada.
            ->renderHook(
                'panels::body.end',
                fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                    view('filament.operaciones.nav-inferior')->render()
                ),
            )
            // El logo usaba el tenant por defecto del usuario: estando en Rivet
            // enlazaba a Link Cargo. Que apunte a la empresa abierta.
            ->homeUrl(fn (): ?string => ($empresa = Filament::getTenant())
                ? route('filament.operaciones.pages.dashboard', ['tenant' => $empresa])
                : null)
            // Todo lo del producto en un grupo: qué existe, con qué receta,
            // cuánto cuesta y cómo se produce.
            ->navigationGroups([
                'Producto',
            ])
            ->discoverResources(
                in: app_path('Filament/Operaciones/Resources'),
                for: 'App\\Filament\\Operaciones\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Operaciones/Pages'),
                for: 'App\\Filament\\Operaciones\\Pages'
            )
            ->userMenuItems(\App\Support\PanelAccess::menuItems('operaciones'))
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
