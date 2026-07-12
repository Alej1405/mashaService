<?php

namespace App\Observers;

use App\Models\StoreOrder;
use App\Modules\Ecommerce\Actions\RecepcionarPedido;

class StoreOrderObserver
{
    /**
     * Al pasar el pedido a 'receptado' (la tienda lo acepta), se gestiona el
     * inventario AUTOMÁTICAMENTE: descuenta lo disponible y mapea producción para
     * lo que falte. Nunca es un paso manual.
     */
    public function updated(StoreOrder $order): void
    {
        if ($order->wasChanged('estado') && $order->estado === 'receptado') {
            app(RecepcionarPedido::class)->handle($order);
        }
    }
}
