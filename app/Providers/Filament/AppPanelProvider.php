<?php

namespace App\Providers\Filament;

use App\Models\Empresa;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\HtmlString;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->login()
            ->tenant(Empresa::class, slugAttribute: 'slug')
            ->colors([
                'primary'   => Color::Indigo,
                'gray'      => Color::Slate,
                'success'   => Color::Emerald,
                'warning'   => Color::Amber,
                'danger'    => Color::Rose,
                'info'      => Color::Sky,
            ])
            ->font('Inter')
            ->brandName('Mashaec ERP')
            ->darkMode(true)
            ->profile(isSimple: false)
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
            ->widgets([
                \App\Filament\App\Widgets\DashboardHeaderWidget::class,
                \App\Filament\App\Widgets\ResumenFinancieroWidget::class,
                \App\Filament\App\Widgets\VentasComprasWidget::class,
                \App\Filament\App\Widgets\StockBajoWidget::class,
                \App\Filament\App\Widgets\TopProductosWidget::class,
                \App\Filament\App\Widgets\FlujoCajaWidget::class,
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
