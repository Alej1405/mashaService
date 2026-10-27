<?php

namespace App\Observers;

use App\Models\StoreOrder;
use App\Models\StoreOrderHistorial;
use App\Modules\Ecommerce\Actions\RecepcionarPedido;

class StoreOrderObserver
{
    /**
     * Al pasar el pedido a 'receptado' (la tienda lo acepta), se gestiona el
     * inventario AUTOMÁTICAMENTE: descuenta lo disponible y mapea producción para
     * lo que falte. Nunca es un paso manual.
     */
    public function created(StoreOrder $order): void
    {
        if (! $order->historialManual) {
            $this->registrar($order);
        }
    }

    public function updated(StoreOrder $order): void
    {
        if ($order->wasChanged('estado')) {
            $this->registrar($order);
        }

        if ($order->wasChanged('estado') && $order->estado === 'receptado') {
            app(RecepcionarPedido::class)->handle($order);
        }
    }

    /** Cada cambio de estado queda en el historial con su fecha y quién lo hizo. */
    private function registrar(StoreOrder $order): void
    {
        StoreOrderHistorial::create([
            'empresa_id' => $order->empresa_id, 'store_order_id' => $order->id,
            'estado' => $order->estado, 'ocurrido_en' => now(), 'user_id' => auth()->id(),
        ]);
    }
}
