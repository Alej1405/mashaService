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
use Filament\Navigation\MenuItem;
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
            ->brandLogo(fn (): ?string => ($t = Filament::getTenant()) && $t->logo_path
                ? \Illuminate\Support\Facades\Storage::disk('public')->url($t->logo_path)
                : null)
            ->brandLogoHeight('2rem')
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
            ->resources([
                \App\Filament\App\Resources\MailTemplateResource::class,
                \App\Filament\App\Resources\MailingContactResource::class,
                \App\Filament\App\Resources\MailCampaignResource::class,
                \App\Filament\App\Resources\EmpresaUserResource::class,
                // CMS
                \App\Filament\App\Resources\CmsServiceResource::class,
                \App\Filament\App\Resources\CmsTeamMemberResource::class,
                \App\Filament\App\Resources\CmsClientLogoResource::class,
                \App\Filament\App\Resources\CmsTestimonialResource::class,
                \App\Filament\App\Resources\CmsFaqResource::class,
                \App\Filament\App\Resources\CmsPostResource::class,
            ])
            ->pages([
                \App\Filament\Basic\Pages\Dashboard::class,
                \App\Filament\Basic\Pages\MailingDashboard::class,
                // CMS — secciones únicas
                \App\Filament\App\Pages\Cms\CmsHeroPage::class,
                \App\Filament\App\Pages\Cms\CmsAboutPage::class,
                \App\Filament\App\Pages\Cms\CmsContactPage::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Panel Pro (ERP)')
                    ->icon('heroicon-o-building-office-2')
                    ->url(fn (): string => '/pro/' . (Filament::getTenant()?->slug ?? ''))
                    ->visible(fn (): bool => \App\Helpers\PlanHelper::can('pro')),
                MenuItem::make()
                    ->label('Panel Enterprise')
                    ->icon('heroicon-o-star')
                    ->url(fn (): string => '/enterprise/' . (Filament::getTenant()?->slug ?? ''))

                    ->visible(fn (): bool => \App\Helpers\PlanHelper::can('enterprise')),
            ])
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
