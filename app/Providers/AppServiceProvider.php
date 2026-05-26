<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Event::listen(Login::class, \App\Listeners\UpdateLastLogin::class);
        // Forzar HTTPS en producción (necesario detrás de Cloudflare)
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        \App\Models\Empresa::observe(\App\Observers\EmpresaObserver::class);
        \App\Models\Purchase::observe(\App\Observers\PurchaseObserver::class);
        \App\Models\InventoryMovement::observe(\App\Observers\InventoryMovementObserver::class);
        \App\Models\Sale::observe(\App\Observers\SaleObserver::class);
        \App\Models\ProductionOrder::observe(\App\Observers\ProductionOrderObserver::class);
        \App\Models\CashMovement::observe(\App\Observers\CashMovementObserver::class);
        \App\Models\Debt::observe(\App\Observers\DebtObserver::class);
        \App\Models\DebtPayment::observe(\App\Observers\DebtPaymentObserver::class);
        \App\Models\Customer::observe(\App\Observers\CustomerPortalObserver::class);
        \App\Models\LogisticsShipment::observe(\App\Observers\LogisticsShipmentObserver::class);
        \App\Models\LogisticsShipmentBill::observe(\App\Observers\LogisticsShipmentBillObserver::class);
        \App\Models\ProductDesign::observe(\App\Observers\ProductDesignObserver::class);
        \App\Models\ServiceDesign::observe(\App\Observers\ServiceDesignObserver::class);

        // CMS — invalidación de caché automática al guardar/eliminar
        $cmsObserver = \App\Observers\CmsObserver::class;
        \App\Models\CmsHero::observe($cmsObserver);
        \App\Models\CmsAbout::observe($cmsObserver);
        \App\Models\CmsService::observe($cmsObserver);
        \App\Models\CmsProduct::observe($cmsObserver);
        \App\Models\CmsTeamMember::observe($cmsObserver);
        \App\Models\CmsClientLogo::observe($cmsObserver);
        \App\Models\CmsTestimonial::observe($cmsObserver);
        \App\Models\CmsFaq::observe($cmsObserver);
        \App\Models\CmsContact::observe($cmsObserver);
        \App\Models\CmsTerminos::observe($cmsObserver);
        \App\Models\CmsPost::observe($cmsObserver);

        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        \Illuminate\Support\Facades\Gate::define('view-reports', function ($user) {
            return $user->hasRole('super_admin') || $user->hasPermissionTo('reportes.ver');
        });
    }
}
