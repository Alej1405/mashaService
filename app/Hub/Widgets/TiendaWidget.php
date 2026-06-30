<?php

namespace App\Hub\Widgets;

use App\Models\Empresa;
use App\Models\StoreOrder;
use App\Models\StoreProduct;

/**
 * Resumen del módulo Tienda en el hub. Solo agregados (count/sum).
 */
class TiendaWidget implements HubWidget
{
    public static function module(): string
    {
        return 'tienda';
    }

    public static function meta(): array
    {
        return [
            'titulo' => 'Tienda',
            'icono'  => 'heroicon-o-shopping-bag',
            'color'  => '#e11d48',
            'path'   => 'store',
        ];
    }

    public static function metrics(Empresa $empresa): array
    {
        return [
            [
                'label' => 'Productos',
                'value' => StoreProduct::where('empresa_id', $empresa->id)->count(),
            ],
            [
                'label' => 'Pedidos',
                'value' => StoreOrder::where('empresa_id', $empresa->id)->count(),
            ],
            [
                'label' => 'Ventas',
                'value' => (float) StoreOrder::where('empresa_id', $empresa->id)->sum('total'),
                'money' => true,
            ],
        ];
    }
}
