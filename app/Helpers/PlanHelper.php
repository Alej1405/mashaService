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
                'Contabilidad General',
                'Ventas',
                'Clientes',
                'Compras',
                'Inventario',
                'Financiamiento',
                'Logística',
                'Mailing',
                'CMS',
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
                'Contabilidad General',
                'Ventas',
                'Clientes',
                'Compras',
                'Inventario',
                'Financiamiento',
                'Logística',
                'Diseño de Producto',
                'Mailing',
                'CMS',
                'Configuración',
            ]
        );
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
