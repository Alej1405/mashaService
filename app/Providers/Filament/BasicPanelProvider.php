<?php

namespace App\Providers\Filament;

use App\Models\Empresa;
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
use Illuminate\Support\HtmlString;
//use Filament\Navigation\MenuItem;
use Filament\Facades\Filament;

class BasicPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('basic')
            ->path('app')
            ->login(\App\Filament\Auth\Login::class)
            ->tenant(Empresa::class, slugAttribute: 'slug')
            ->tenantProfile(\App\Filament\Pages\Tenancy\EditEmpresaProfile::class)
            ->colors([
                'primary'   => Color::Slate,
                'gray'      => Color::Slate,
                'success'   => Color::Emerald,
                'warning'   => Color::Amber,
                'danger'    => Color::Rose,
                'info'      => Color::Sky,
            ])
            ->font('Inter')
            ->brandName(fn (): string => Filament::getTenant()?->name ?? 'Mashaec ERP')
            ->brandLogo(fn (): ?string => ($logo = Filament::getTenant()?->logo_path)
                ? asset('storage/' . ltrim($logo, '/'))
                : null)
            ->favicon(fn (): ?string => ($logo = Filament::getTenant()?->logo_path)
                ? asset('storage/' . ltrim($logo, '/'))
                : null)
            ->brandLogoHeight('2rem')
            ->darkMode(false)
            ->topNavigation(false)
            ->Navigation(false)
            ->font('Sansation')
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
            ->broadcasting()
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make('Mailing')->collapsible(),
                \Filament\Navigation\NavigationGroup::make('CMS')->collapsible(),
                \Filament\Navigation\NavigationGroup::make('Blog')->collapsible(),
            ])
            ->discoverResources(
                in: app_path('Filament/App/Resources'),
                for: 'App\\Filament\\App\\Resources'
            )
            ->pages([
                \App\Filament\Basic\Pages\Dashboard::class,
                \App\Filament\Basic\Pages\MailingDashboard::class,
                // CMS — secciones únicas
                \App\Filament\App\Pages\Cms\CmsHeroPage::class,
                \App\Filament\App\Pages\Cms\CmsAboutPage::class,
                \App\Filament\App\Pages\Cms\CmsContactPage::class,
                // Carta de Presentación
                \App\Filament\App\Pages\CartaPresentacionPage::class,
                // Soporte
                \App\Filament\App\Pages\MiChatSoportePage::class,
            ])
            ->userMenuItems(\App\Support\PanelAccess::menuItems('basic'))
            ->widgets([
                \App\Filament\App\Widgets\DashboardHeaderWidget::class,
            ])
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
