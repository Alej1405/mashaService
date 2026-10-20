<?php

namespace App\Filament\Contabilidad\Pages;

use App\Modules\Compras\Queries\ComprasElectronicasPendientes;
use Filament\Facades\Filament;
use Filament\Pages\Page;

/**
 * Facturas de compra que llegaron (correo, Telegram, XML subido) y no se
 * registraron solas. Usa el mismo bloque que la bandeja del portal y que
 * Operaciones: subir los XML y completar cada factura en la misma pantalla.
 */
class ComprasPorCompletar extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Compras por completar';
    protected static ?string $navigationGroup = 'Día a día';
    protected static ?string $title           = 'Compras por completar';
    protected static ?int    $navigationSort  = 2;
    protected static string  $view            = 'filament.contabilidad.compras-por-completar';

    /** En Contabilidad esto se hace en la bandeja (pestaña «Esperan»); queda la página para los enlaces de la campana. */
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return \App\Helpers\PlanHelper::hasModule('finanzas');
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
