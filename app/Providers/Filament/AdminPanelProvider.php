<?php

namespace App\Providers\Filament;

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
            ->renderHook('panels::head.end', fn (): HtmlString => new HtmlString('<style>
                .grid-cols-5{grid-template-columns:repeat(5,minmax(0,1fr))}
                .grid-cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}
                @media(min-width:1024px){
                    .lg\:grid-cols-5{grid-template-columns:repeat(5,minmax(0,1fr))}
                    .lg\:grid-cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}
                    .lg\:grid-cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}
                    .lg\:col-span-2{grid-column:span 2/span 2}
                }
            </style>'))
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
