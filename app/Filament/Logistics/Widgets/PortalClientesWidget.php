<?php

namespace App\Filament\Logistics\Widgets;

use Filament\Widgets\Widget;

/**
 * Muestra el acceso al portal público de clientes en el dashboard de Logística,
 * que es una grilla de widgets (sin Blade propio como los otros paneles).
 * El componente <x-portal-clientes-card /> resuelve la URL del tenant activo.
 */
class PortalClientesWidget extends Widget
{
    protected static string $view = 'filament.logistics.widgets.portal-clientes';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = -10;
}
