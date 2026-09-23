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
 * Panel de Contabilidad.
 *
 * Quien lleva los libros y responde ante el SRI y la Superintendencia. Se
 * separa del ERP porque su usuario es otro —el contador— y su calendario
 * también: el mes cierra con declaraciones y el año con estados financieros.
 *
 * Igual que Operaciones: sin el tema oscuro del panel App, con aire vertical
 * propio y con navegación inferior en celular y tablet.
 */
class ContabilidadPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('contabilidad')
            ->path('contabilidad')
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
            // La campana: por ahí llega el aviso de que la declaración terminó.
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->brandName(fn (): string => Filament::getTenant()?->name ?? 'Contabilidad')
            ->brandLogo(fn (): ?string => ($logo = Filament::getTenant()?->logo_path)
                && \Illuminate\Support\Facades\Storage::disk('public')->exists($logo)
                    ? asset('storage/' . ltrim($logo, '/'))
                    : null)
            ->brandLogoHeight('2rem')
            ->darkMode(false)
            // El contenedor de Filament trae padding lateral pero no vertical.
            ->renderHook(
                'panels::head.end',
                fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                    '<style>.fi-main{padding-top:2rem;padding-bottom:2.5rem}'
                    . '@media(max-width:640px){.fi-main{padding-top:1.25rem;padding-bottom:1.75rem}}</style>'
                ),
            )
            // En celular y tablet la navegación va abajo: es una app, no una web.
            ->renderHook(
                'panels::body.end',
                fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString(
                    view('filament.contabilidad.nav-inferior')->render()
                ),
            )
            ->homeUrl(fn (): ?string => ($empresa = Filament::getTenant())
                ? route('filament.contabilidad.pages.dashboard', ['tenant' => $empresa])
                : null)
            ->discoverResources(
                in: app_path('Filament/Contabilidad/Resources'),
                for: 'App\\Filament\\Contabilidad\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Contabilidad/Pages'),
                for: 'App\\Filament\\Contabilidad\\Pages'
            )
            ->userMenuItems(\App\Support\PanelAccess::menuItems('contabilidad'))
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
            ->authMiddleware([Authenticate::class]);
    }
}
