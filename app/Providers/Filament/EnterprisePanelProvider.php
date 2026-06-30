<?php

namespace App\Providers\Filament;

use App\Models\Empresa;
use App\Http\Middleware\FilamentAuthenticate;
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
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationItem;
use Filament\Facades\Filament;

class EnterprisePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('enterprise')
            ->path('enterprise')
            ->tenant(Empresa::class, slugAttribute: 'slug')
            ->tenantProfile(\App\Filament\Pages\Tenancy\EditEmpresaProfile::class)
            ->colors([
                'primary'   => Color::Indigo,
                'gray'      => Color::Slate,
                'success'   => Color::Emerald,
                'warning'   => Color::Amber,
                'danger'    => Color::Rose,
                'info'      => Color::Sky,
            ])
            ->font('Inter')
            ->brandName(fn (): string => Filament::getTenant()?->name ?? 'Mashaec ERP')
            ->brandLogo(fn (): ?string => ($t = Filament::getTenant()) && $t->logo_path
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($t->logo_path)
                : null)
            ->favicon(fn (): ?string => ($t = Filament::getTenant()) && $t->logo_path
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($t->logo_path)
                : null)
            ->brandLogoHeight('2rem')
            ->darkMode(false)
            ->font('Sansation')
            ->renderHook(
                'panels::head.done',
                fn (): HtmlString => new HtmlString('
                    <link rel="stylesheet" href="' . asset('css/aura-glass.css') . '">
                    <link rel="stylesheet" href="' . asset('css/filament/app/theme.css') . '">
                    <script>localStorage.setItem("theme","dark");</script>
                '),
            )
            ->renderHook(
                'panels::body.start',
                fn (): string => view('filament.loading')->render(),
            )
            ->navigationGroups(\App\Helpers\PlanHelper::enterpriseNavigationGroups())
            ->discoverResources(
                in: app_path('Filament/App/Resources'),
                for: 'App\\Filament\\App\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/App/Pages'),
                for: 'App\\Filament\\App\\Pages'
            )
            ->pages([
                \App\Filament\App\Pages\Dashboard::class,
            ])
            ->navigationItems([
                NavigationItem::make('Portal de Clientes')
                    ->icon('heroicon-o-globe-alt')
                    ->sort(-1)
                    ->url(fn (): string => route('portal.login', ['slug' => Filament::getTenant()?->slug ?? '']))
                    ->openUrlInNewTab(),
            ])
            ->userMenuItems(\App\Support\PanelAccess::menuItems('enterprise'))
            ->widgets([
                \App\Filament\App\Widgets\DashboardHeaderWidget::class,
                \App\Filament\App\Widgets\KpiFinancieroWidget::class,
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
                FilamentAuthenticate::class,
                \App\Http\Middleware\EnsureNotMarketingRole::class,
            ]);
    }
}
