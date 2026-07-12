<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Event::listen(Login::class, \App\Listeners\UpdateLastLogin::class);

        // Rate limiter de la superficie n8n: por sesión/chat (todo n8n sale de la
        // misma IP del VPS, así que limitar por IP sería demasiado grueso).
        RateLimiter::for('n8n', function (Request $request) {
            $key = $request->bearerToken()
                ?: $request->input('chat_id')
                ?: $request->ip();

            return Limit::perMinute((int) config('n8n.rate_limit', 60))
                ->by('n8n:'.sha1((string) $key));
        });
        // Forzar HTTPS en producción (necesario detrás de Cloudflare)
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Sidebar en modo ACORDEÓN: al abrir un grupo, colapsa los demás (solo uno
        // abierto a la vez) para no contaminar la visión. Complementa el
        // ->collapsed() por defecto de cada grupo. Filament no lo trae nativo:
        // se parchea el store Alpine del sidebar (regla del proyecto).
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            \Filament\View\PanelsRenderHook::BODY_END,
            fn (): string => <<<'HTML'
<script>
(function () {
    function labels() {
        var out = [];
        document.querySelectorAll('.fi-sidebar-group').forEach(function (el) {
            try { var d = Alpine.$data(el); if (d && d.label && out.indexOf(d.label) === -1) out.push(d.label); } catch (e) {}
        });
        return out;
    }
    // Acordeón: al abrir un grupo, colapsa los demás (una sola vez por store).
    var iv = setInterval(function () {
        if (typeof Alpine === 'undefined' || ! Alpine.store) return;
        var store = Alpine.store('sidebar');
        if (! store || typeof store.toggleCollapsedGroup !== 'function') return;
        if (! Array.isArray(store.collapsedGroups)) store.collapsedGroups = [];
        if (! store.__accordion) {
            store.__accordion = true;
            var orig = store.toggleCollapsedGroup.bind(store);
            store.toggleCollapsedGroup = function (group) {
                var willExpand = this.groupIsCollapsed(group);
                orig(group);
                if (willExpand) {
                    labels().forEach(function (l) {
                        if (l !== group && ! store.groupIsCollapsed(l)) orig(l);
                    });
                }
            };
        }
        clearInterval(iv);
    }, 80);
    setTimeout(function () { clearInterval(iv); }, 10000);

    // Colapsado por defecto: en cada carga completa (no en navegación SPA) colapsa
    // TODOS los grupos, sin depender del localStorage.
    if (! window.__mashaSidebarCollapsed) {
        var iv2 = setInterval(function () {
            if (typeof Alpine === 'undefined' || ! Alpine.store) return;
            var store = Alpine.store('sidebar');
            if (! store) return;
            var ls = labels();
            if (! ls.length) return;
            if (! Array.isArray(store.collapsedGroups)) store.collapsedGroups = [];
            store.collapsedGroups = ls.slice();
            window.__mashaSidebarCollapsed = true;
            clearInterval(iv2);
        }, 80);
        setTimeout(function () { clearInterval(iv2); }, 10000);
    }
})();
</script>
HTML
        );

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
        \App\Models\StoreOrder::observe(\App\Observers\StoreOrderObserver::class);

        // CMS — invalidación de caché automática al guardar/eliminar
        $cmsObserver = \App\Observers\CmsObserver::class;
        \App\Models\CmsHero::observe($cmsObserver);
        \App\Models\CmsAbout::observe($cmsObserver);
        \App\Models\CmsService::observe($cmsObserver);
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
