<?php

namespace App\Helpers;

use Filament\Facades\Filament;

/**
 * Helper para verificar el nivel de plan del tenant activo.
 *
 * Uso en Resources/Pages del panel App:
 *   public static function canAccess(): bool
 *   {
 *       return \App\Helpers\PlanHelper::can('pro');
 *   }
 *
 * Regla: SIEMPRE agregar canAccess() en cualquier Resource/Page nuevo del panel App.
 */
class PlanHelper
{
    /** Jerarquía de planes. Cuanto mayor el número, más acceso. */
    private const LEVELS = [
        'basic'      => 1,
        'pro'        => 2,
        'enterprise' => 3,
    ];

    /**
     * Retorna true si el plan del tenant activo es igual o superior al mínimo requerido.
     * En caso de error (contexto sin tenant) usa 'basic' como fallback seguro.
     */
    public static function can(string $minimumPlan): bool
    {
        $current = self::current();
        return (self::LEVELS[$current] ?? 1) >= (self::LEVELS[$minimumPlan] ?? 1);
    }

    /**
     * Retorna el plan actual del tenant.
     */
    public static function current(): string
    {
        try {
            return Filament::getTenant()?->plan ?? 'basic';
        } catch (\Throwable) {
            return 'basic';
        }
    }

    /**
     * Grupos de navegación del panel App / Pro (orden del sidebar).
     *
     * @return \Filament\Navigation\NavigationGroup[]
     */
    public static function navigationGroups(): array
    {
        return array_map(
            fn (string $name) => \Filament\Navigation\NavigationGroup::make($name)->collapsible(),
            [
                'Ventas',
                'Producción',
                'Inventario',
                'Contabilidad',
                'Logística',
                'Informes',
                'Configuración',
            ]
        );
    }

    /**
     * Grupos de navegación del panel Enterprise (incluye grupos exclusivos).
     *
     * @return \Filament\Navigation\NavigationGroup[]
     */
    public static function enterpriseNavigationGroups(): array
    {
        return array_map(
            fn (string $name) => \Filament\Navigation\NavigationGroup::make($name)->collapsible(),
            [
                'Ventas',
                'Producción',
                'Inventario',
                'Contabilidad',
                'Logística',
                'Diseño de Producto',
                'Informes',
                'Configuración',
            ]
        );
    }

    /** Cache por request de los módulos de cada panel (key => [module_keys]). */
    private static array $panelModulesCache = [];

    /** Cache por request de los módulos del rol del usuario en el tenant actual. */
    private static ?array $roleModulesCache = null;
    private static bool $roleModulesResolved = false;

    /**
     * Fuente única de verdad para la VISIBILIDAD de módulos en el panel App.
     *
     * La visibilidad es la INTERSECCIÓN de dos capas:
     *   1. Panel actual  → módulos del panel (panel_modules), definidos por el plan.
     *   2. Rol del user  → módulos del rol (role_module), definidos por el super_admin.
     *
     * Un módulo se ve solo si pertenece al panel donde navegas Y al rol del usuario
     * en la empresa activa. super_admin (o rol no determinable) no se restringe por rol.
     *
     * Los módulos siempre funcionan en segundo plano (Observers, AccountingService);
     * esto solo controla si aparecen en la navegación / si su ruta es accesible.
     *
     * Usar en canAccess() de todos los Resources/Pages del panel App.
     */
    public static function hasModule(string $module): bool
    {
        try {
            $panelKey = Filament::getCurrentPanel()?->getId();
            if (! $panelKey) {
                return false;
            }

            // Capa 1: ¿el panel actual muestra este módulo?
            if (! isset(self::$panelModulesCache[$panelKey])) {
                self::$panelModulesCache[$panelKey] =
                    \App\Models\Panel::where('key', $panelKey)->first()?->moduleKeys() ?? [];
            }
            if (! in_array($module, self::$panelModulesCache[$panelKey], true)) {
                return false;
            }

            // Capa 2: ¿el rol del usuario ve este módulo? (null = sin restricción)
            $roleModules = self::currentRoleModules();
            if ($roleModules === null) {
                return true;
            }

            return in_array($module, $roleModules, true);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Módulos visibles para el rol del usuario en la empresa activa.
     *
     * Devuelve null cuando NO debe aplicarse la restricción por rol:
     *   - usuario super_admin (ve todo),
     *   - sin usuario autenticado, o
     *   - rol no determinable (fallback seguro: no romper accesos existentes).
     *
     * El rol se toma del pivote empresa_user_access (preciso por empresa) y, si no
     * existe, del rol global Spatie como respaldo.
     *
     * @return array<int,string>|null
     */
    private static function currentRoleModules(): ?array
    {
        if (self::$roleModulesResolved) {
            return self::$roleModulesCache;
        }
        self::$roleModulesResolved = true;

        $user = auth()->user();
        if (! $user || $user->hasRole('super_admin')) {
            return self::$roleModulesCache = null;
        }

        $rolName = null;
        $tenant  = Filament::getTenant();
        if ($tenant) {
            $rolName = $user->empresasAcceso()
                ->where('empresas.id', $tenant->id)
                ->first()?->pivot->rol;
        }
        $rolName = $rolName ?: $user->getRoleNames()->first();

        if (! $rolName) {
            return self::$roleModulesCache = null;
        }

        return self::$roleModulesCache =
            \App\Models\Role::where('name', $rolName)->first()?->moduleKeys() ?? [];
    }

    /**
     * Verifica si el tenant tiene una feature específica activa en su JSONB.
     * Usa dot-notation: 'marketing.cms.hero', 'logistica.activo', etc.
     * Requiere plan mínimo + feature habilitada.
     */
    public static function canFeature(string $dotPath, string $minimumPlan = 'pro'): bool
    {
        if (! self::can($minimumPlan)) {
            return false;
        }

        try {
            $tenant = Filament::getTenant();
            return $tenant?->hasFeature($dotPath) ?? false;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Etiqueta legible del plan.
     */
    public static function label(string $plan): string
    {
        return match ($plan) {
            'basic'      => 'Basic',
            'pro'        => 'Pro',
            'enterprise' => 'Enterprise',
            default      => ucfirst($plan),
        };
    }

    /**
     * Color Filament del badge del plan.
     */
    public static function color(string $plan): string
    {
        return match ($plan) {
            'basic'      => 'gray',
            'pro'        => 'info',
            'enterprise' => 'warning',
            default      => 'gray',
        };
    }
}
