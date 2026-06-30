<?php

namespace App\Hub;

use App\Hub\Widgets\HubWidget;

/**
 * Mapa módulo → widget del hub. Agregar un widget nuevo = registrar su clase
 * aquí (y crearla en app/Hub/Widgets). El hub solo muestra el widget de un
 * módulo si el usuario ve ese módulo (plan ∩ rol).
 */
class HubWidgetRegistry
{
    /** @var array<string,class-string<HubWidget>> */
    public const WIDGETS = [
        'tienda'    => Widgets\TiendaWidget::class,
        'marketing' => Widgets\MarketingWidget::class,
    ];

    /** @return class-string<HubWidget>|null */
    public static function for(string $module): ?string
    {
        return self::WIDGETS[$module] ?? null;
    }
}
