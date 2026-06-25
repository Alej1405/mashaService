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
