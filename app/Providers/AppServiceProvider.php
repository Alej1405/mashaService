<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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

        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        \Illuminate\Support\Facades\Gate::define('view-reports', function ($user) {
            return $user->hasRole('super_admin') || $user->hasPermissionTo('reportes.ver');
        });
    }
}
