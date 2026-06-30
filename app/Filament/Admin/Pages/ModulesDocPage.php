<?php

namespace App\Filament\Admin\Pages;

use App\Shared\Documentation\ModuleDocScanner;
use Filament\Pages\Page;

/**
 * Documentación viva de módulos CMS y Tienda (primera iteración).
 * Auto-generada desde config/erp_features.php y atributos #[Documentado].
 * Solo super_admin. Sin datos sensibles de clientes.
 */
class ModulesDocPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Plataforma';
    protected static ?string $navigationLabel = 'Módulos';
    protected static ?string $title           = 'Módulos del sistema';
    protected static ?int    $navigationSort  = 2;

    protected static string $view = 'filament.admin.pages.modules-doc';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    /** Módulos con documentación completa (CMS y Tienda en esta iteración). */
    public function getModulesData(): array
    {
        return app(ModuleDocScanner::class)->scan();
    }

    /** Actions y Queries escaneadas por reflexión. */
    public function getActionsData(): array
    {
        return app(ModuleDocScanner::class)->scanClasses();
    }
}
