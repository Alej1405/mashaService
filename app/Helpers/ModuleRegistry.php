<?php

namespace App\Helpers;

use App\Models\Panel;

/**
 * Catálogo módulo → Resources/Pages del panel App.
 *
 * Fuente única de verdad de a qué módulo pertenece cada Resource/Page. La
 * Fase 2 usará este mapa para decidir la VISIBILIDAD en la navegación según
 * el panel de la empresa. Los módulos siempre funcionan; esto solo agrupa.
 *
 * Clases "core" (módulo = null) se muestran SIEMPRE en cualquier panel:
 * Dashboard, Settings, gestión de usuarios, soporte, docs API.
 *
 * Fase 1: solo el mapa + helpers. Ningún flujo lo lee todavía.
 */
class ModuleRegistry
{
    private const R = 'App\\Filament\\App\\Resources\\';
    private const P = 'App\\Filament\\App\\Pages\\';

    /** @var array<string,array<int,string>>|null cache del mapa resuelto */
    private static ?array $resolved = null;

    /**
     * Definición módulo → [clases relativas]. Prefijo R = Resources, P = Pages.
     * El bucket '_core' agrupa lo que se ve siempre (módulo null).
     */
    private static function definition(): array
    {
        return [
            'finanzas' => [
                'r' => ['AccountPlanResource', 'CostoFijoResource', 'JournalEntryResource'],
                'p' => [
                    'AccountingMapPage', 'EstadoSituacionFinanciera',
                    'Reports\\BalanceComprobacion', 'Reports\\BalanceGeneral',
                    'Reports\\EstadoResultados', 'Reports\\FlujoCaja',
                    'Reports\\InformesIndex', 'Reports\\LibroDiario', 'Reports\\LibroMayor',
                ],
            ],
            'tesoreria' => [
                'r' => [
                    'BankAccountResource', 'CashMovementResource', 'CashRegisterResource',
                    'CashSessionResource', 'CreditCardResource', 'DebtResource',
                ],
                'p' => ['CajaIndex', 'DebtReportPage'],
            ],
            'compras' => [
                'r' => ['ItemRequestResource', 'PurchaseResource', 'SupplierResource'],
                'p' => [],
            ],
            'inventario' => [
                'r' => ['AlmacenResource', 'InventoryItemResource', 'MeasurementUnitResource'],
                'p' => ['ImportarInventarioPage'],
            ],
            'ventas' => [
                'r' => ['CustomerResource', 'SaleResource'],
                'p' => [],
            ],
            'produccion' => [
                'r' => [
                    'ProductDesignResource', 'ProductionOrderResource',
                    'ServiceChargeConfigResource', 'ServiceDesignResource',
                ],
                'p' => ['PlanificacionPage', 'ProduccionPage'],
            ],
            'marketing' => [
                'r' => [
                    'CmsClientLogoResource', 'CmsFaqResource', 'CmsPostResource',
                    'CmsServiceResource', 'CmsTeamMemberResource',
                    'CmsTestimonialResource', 'MailCampaignResource', 'MailTemplateResource',
                    'MailingContactResource', 'MailingGroupResource',
                ],
                'p' => [
                    'Cms\\CmsAboutPage', 'Cms\\CmsContactPage', 'Cms\\CmsHeroPage',
                    'Cms\\CmsTerminosPage', 'MailingDashboard', 'CartaPresentacionPage',
                ],
            ],
            'tienda' => [
                'r' => [
                    'ServiceContractResource', 'StoreCategoryResource', 'StoreCouponResource',
                    'StoreCustomerResource', 'StoreOrderResource', 'StoreProductResource',
                ],
                'p' => [],
            ],
            'logistica' => [
                'r' => [
                    'LogisticsBillingRequestResource', 'LogisticsPaymentClaimResource',
                    'LogisticsShipmentBillResource',
                ],
                'p' => [],
            ],
            '_core' => [
                'r' => ['EmpresaUserResource', 'SupportTicketResource'],
                'p' => ['Dashboard', 'Settings', 'MiChatSoportePage'],
            ],
        ];
    }

    /**
     * Mapa resuelto: FQCN completo → clave de módulo ('_core' para los siempre-visibles).
     *
     * @return array<string,string>
     */
    public static function map(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $map = [];
        foreach (self::definition() as $module => $buckets) {
            foreach ($buckets['r'] as $rel) {
                $map[self::R . $rel] = $module;
            }
            foreach ($buckets['p'] as $rel) {
                $map[self::P . $rel] = $module;
            }
        }

        return self::$resolved = $map;
    }

    /**
     * Módulo al que pertenece una clase, o null si es core (siempre visible)
     * o desconocida.
     */
    public static function moduleFor(string $class): ?string
    {
        $module = self::map()[ltrim($class, '\\')] ?? null;
        return ($module === null || $module === '_core') ? null : $module;
    }

    /** ¿La clase es core (se muestra siempre, sin importar el panel)? */
    public static function isCore(string $class): bool
    {
        return (self::map()[ltrim($class, '\\')] ?? null) === '_core';
    }

    /**
     * FQCN de todas las clases core (siempre visibles).
     *
     * @return array<int,string>
     */
    public static function coreClasses(): array
    {
        return array_keys(array_filter(self::map(), fn ($m) => $m === '_core'));
    }

    /**
     * FQCN de Resources/Pages de un módulo.
     *
     * @return array<int,string>
     */
    public static function classesForModule(string $moduleKey): array
    {
        return array_keys(array_filter(self::map(), fn ($m) => $m === $moduleKey));
    }

    /**
     * Claves de módulos visibles en un panel (lee la BD).
     *
     * @return array<int,string>
     */
    public static function modulesForPanel(string $panelKey): array
    {
        return Panel::where('key', $panelKey)->first()?->moduleKeys() ?? [];
    }

    /**
     * Todas las clases visibles en un panel: core + las de sus módulos.
     *
     * @return array<int,string>
     */
    public static function classesForPanel(string $panelKey): array
    {
        $classes = self::coreClasses();
        foreach (self::modulesForPanel($panelKey) as $moduleKey) {
            $classes = array_merge($classes, self::classesForModule($moduleKey));
        }
        return array_values(array_unique($classes));
    }

    /**
     * Claves de todos los módulos del catálogo (config/erp_features.php).
     *
     * @return array<int,string>
     */
    public static function allModuleKeys(): array
    {
        return array_keys(config('erp_features', []));
    }
}
