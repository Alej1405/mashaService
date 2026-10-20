<?php

namespace App\Filament\Operaciones\Pages;

use App\Modules\Compras\Queries\ComprasElectronicasPendientes;
use Filament\Facades\Filament;
use Filament\Pages\Page;

/**
 * Facturas cuyos productos el inventario no conoce. Usa el mismo bloque que
 * Contabilidad: subir los XML y, en la misma pantalla, decir qué es cada
 * producto nuevo y registrar la factura. Se dice una vez por producto.
 */
class ProductosPorConfigurar extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Facturas por completar';
    protected static ?string $title           = 'Facturas por completar';
    protected static ?int    $navigationSort  = 3;
    protected static string  $view            = 'filament.operaciones.productos-por-configurar';

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('inventario');
    }

    public static function getNavigationBadge(): ?string
    {
        $empresa = Filament::getTenant();
        $n = $empresa ? app(ComprasElectronicasPendientes::class)->compras($empresa->id)->count() : 0;

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
