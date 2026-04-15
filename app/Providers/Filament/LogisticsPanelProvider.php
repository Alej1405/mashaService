<?php

namespace App\Providers\Filament;

use App\Models\Empresa;
use App\Http\Middleware\FilamentAuthenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
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

class LogisticsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('logistics')
            ->path('logistics')
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
            ->font('Inter')
            ->brandName(fn (): string => (Filament::getTenant()?->name ?? 'Mashaec') . ' · Logística')
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
                    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js" defer></script>
                '),
            )
            ->renderHook(
                'panels::body.start',
                fn (): string => view('filament.loading')->render(),
            )
            ->navigationGroups([
                NavigationGroup::make('Bodegas')->collapsible(false),
                NavigationGroup::make('Importaciones')->collapsible(false),
                NavigationGroup::make('Configuración')->collapsible(),
            ])
            ->resources([
                \App\Filament\Logistics\Resources\PackageResource::class,
                \App\Filament\Logistics\Resources\ConsignatarioResource::class,
                \App\Filament\Logistics\Resources\ShipmentResource::class,
            ])
            ->pages([
                \App\Filament\Logistics\Pages\Dashboard::class,
                \App\Filament\Logistics\Pages\BodegaEEUUPage::class,
                \App\Filament\Logistics\Pages\BodegaEspanaPage::class,
                \App\Filament\Logistics\Pages\ShipmentKanban::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Panel ERP')
                    ->icon('heroicon-o-building-office-2')
                    ->url(fn (): string => '/pro/' . (Filament::getTenant()?->slug ?? '')),
                MenuItem::make()
                    ->label('Panel Enterprise')
                    ->icon('heroicon-o-star')
                    ->url(fn (): string => '/enterprise/' . (Filament::getTenant()?->slug ?? '')),
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
            ]);
    }
}
