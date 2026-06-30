<?php

namespace App\Support;

use App\Models\Panel;
use App\Models\Role;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Navigation\MenuItem;

/**
 * Fuente ÚNICA de los paneles que el usuario actual puede abrir en la empresa
 * activa (tenant), ya filtrados por la intersección Plan (plan_panel) ∩ Rol
 * (role_module). La usan tanto el hub de inicio como el menú de cambio de panel,
 * para que ambos sean siempre coherentes.
 *
 * El tenant es la piedra angular: todo se resuelve sobre Filament::getTenant().
 */
class PanelAccess
{
    /** Color de cada panel (key del modelo Panel) → hex de acento. */
    private const COLOR_HEX = [
        'slate'   => '#64748b', 'gray'    => '#6b7280', 'indigo'  => '#6366f1',
        'amber'   => '#d97706', 'cyan'    => '#0891b2', 'violet'  => '#7c3aed',
        'emerald' => '#059669', 'rose'    => '#e11d48', 'sky'     => '#0284c7',
    ];

    /**
     * Metadatos de navegación de los 6 paneles reales (fijos por código Filament).
     * Define etiqueta, ícono y orden del menú de cambio de panel.
     */
    private const NAV = [
        'basic'      => ['label' => 'Inicio',            'icon' => 'heroicon-o-home',             'path' => 'app'],
        'pro'        => ['label' => 'Panel Pro (ERP)',   'icon' => 'heroicon-o-building-office-2', 'path' => 'pro'],
        'enterprise' => ['label' => 'Panel Enterprise',  'icon' => 'heroicon-o-star',             'path' => 'enterprise'],
        'logistics'  => ['label' => 'Panel Logística',   'icon' => 'heroicon-o-truck',            'path' => 'logistics'],
        'cms'        => ['label' => 'Panel CMS',         'icon' => 'heroicon-o-globe-alt',        'path' => 'cms'],
        'ecommerce'  => ['label' => 'Panel Tienda',      'icon' => 'heroicon-o-shopping-bag',     'path' => 'store'],
    ];

    /** Cache por request del resultado (el tenant es fijo dentro de un request). */
    private static ?array $cache = null;

    /**
     * Paneles accesibles con metadata completa (para el hub de inicio).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function accessiblePanels(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $empresa = Filament::getTenant();
        $user    = auth()->user();
        if (! $empresa || ! $user) {
            return self::$cache = [];
        }

        $isSuper  = $user->hasRole('super_admin');
        $catalogo = config('erp_features', []);

        $rolName = $user->empresasAcceso()
            ->where('empresas.id', $empresa->id)
            ->first()?->pivot->rol
            ?: $user->getRoleNames()->first();

        $rolModules = $isSuper ? null : (Role::where('name', $rolName)->first()?->moduleKeys() ?? []);

        // Paneles que abre el plan (plan_panel, por id) + los basados en rol.
        $candidatos = collect($empresa->servicePlan?->panels()->where('activo', true)->orderBy('sort')->get() ?? collect());

        $roleBased = User::roleBasedPanels();
        foreach ($roleBased as $key => $roles) {
            if ($isSuper || in_array($rolName, $roles, true)) {
                $panel = Panel::where('key', $key)->where('activo', true)->first();
                if ($panel && ! $candidatos->contains('id', $panel->id)) {
                    $candidatos->push($panel);
                }
            }
        }

        $out = [];
        foreach ($candidatos as $panel) {
            $modulosPanel = $panel->moduleKeys();
            $esRoleBased  = isset($roleBased[$panel->key]);

            $visibles = ($rolModules === null || $esRoleBased)
                ? $modulosPanel
                : array_values(array_intersect($modulosPanel, $rolModules));

            if (empty($visibles) && ! $esRoleBased && ! $isSuper) {
                continue;
            }

            $out[] = [
                'key'        => $panel->key,
                'nombre'     => $panel->name,
                'icono'      => $panel->icon ?: 'heroicon-o-squares-2x2',
                'color'      => self::COLOR_HEX[$panel->color] ?? self::COLOR_HEX['slate'],
                'url'        => '/' . $panel->path . '/' . ($empresa->slug ?? ''),
                'modulos'    => collect($visibles)->map(fn ($k) => $catalogo[$k]['label'] ?? $k)->all(),
                'moduloKeys' => array_values($visibles),
            ];
        }

        return self::$cache = $out;
    }

    /** Claves de los paneles accesibles por el usuario actual. */
    public static function accessibleKeys(): array
    {
        return array_column(self::accessiblePanels(), 'key');
    }

    /** Claves de los módulos visibles para el usuario (unión sobre sus paneles). */
    public static function accessibleModuleKeys(): array
    {
        return collect(self::accessiblePanels())
            ->flatMap(fn (array $p): array => $p['moduloKeys'])
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Ítems de cambio de panel para el menú de usuario, generados dinámicamente.
     * Excluye el panel actual y solo muestra (vía ->visible) los accesibles.
     *
     * @return array<int,MenuItem>
     */
    public static function menuItems(string $currentPanelKey): array
    {
        $items = [];

        foreach (self::NAV as $key => $meta) {
            if ($key === $currentPanelKey) {
                continue;
            }

            $items[] = MenuItem::make()
                ->label($meta['label'])
                ->icon($meta['icon'])
                ->url(fn (): string => '/' . $meta['path'] . '/' . (Filament::getTenant()?->slug ?? ''))
                ->visible(fn (): bool => in_array($key, self::accessibleKeys(), true));
        }

        return $items;
    }
}
