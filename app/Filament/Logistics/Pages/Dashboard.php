<?php

namespace App\Filament\Logistics\Pages;

use App\Filament\Logistics\Widgets\PortalClientesWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon  = 'heroicon-o-home';
    protected static ?string $title           = 'Inicio';
    protected static ?string $navigationLabel = 'Inicio';
    protected static ?int    $navigationSort  = -2;

    public function getColumns(): int|string|array
    {
        return ['default' => 1, 'sm' => 2, 'lg' => 3];
    }

    /** Acceso al portal público de clientes, arriba de los widgets de logística. */
    protected function getHeaderWidgets(): array
    {
        return [
            PortalClientesWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|string|array
    {
        return 1;
    }
}
