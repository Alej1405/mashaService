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
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make('E-Commerce')->collapsible(),
                \Filament\Navigation\NavigationGroup::make('Inventario')->collapsible(),
                \Filament\Navigation\NavigationGroup::make('Diseño de Producto')->collapsible(),
                \Filament\Navigation\NavigationGroup::make('Planificación')->collapsible(),
            ])
            ->resources([
                \App\Filament\App\Resources\CostoFijoResource::class,
                \App\Filament\App\Resources\StoreProductResource::class,
                \App\Filament\App\Resources\StoreCategoryResource::class,
                \App\Filament\App\Resources\StoreOrderResource::class,
                \App\Filament\App\Resources\StoreCustomerResource::class,
                \App\Filament\App\Resources\StoreCouponResource::class,
                \App\Filament\App\Resources\InventoryItemResource::class,
                \App\Filament\App\Resources\ProductDesignResource::class,
                \App\Filament\App\Resources\ItemRequestResource::class,
            ])
            ->pages([
                \App\Filament\App\Pages\Dashboard::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Panel Mailing')
                    ->icon('heroicon-o-envelope')
                    ->url(fn (): string => '/app/' . (Filament::getTenant()?->slug ?? '')),
                MenuItem::make()
                    ->label('Panel Pro (ERP)')
                    ->icon('heroicon-o-building-office-2')
                    ->url(fn (): string => '/pro/' . (Filament::getTenant()?->slug ?? '')),
            ])
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
                \App\Http\Middleware\EnsureNotMarketingRole::class,
            ]);
    }
}
