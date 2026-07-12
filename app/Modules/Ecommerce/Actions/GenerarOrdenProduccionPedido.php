<?php

namespace App\Modules\Ecommerce\Actions;

use App\Models\StoreOrder;
use App\Shared\Attributes\Documentado;
use Illuminate\Support\Facades\Log;

/**
 * STUB — solo MAPEADO. Cuando un pedido receptado no tiene stock suficiente, aquí
 * se generará la orden de producción (PROD-YYYY-#####) por cada producto faltante,
 * enlazada al pedido. El módulo Producción todavía NO está en alcance, así que por
 * ahora únicamente se registra la intención, sin efectos contables ni de inventario.
 *
 * Punto de extensión: al habilitar Producción, crear la ProductionOrder real aquí.
 */
#[Documentado(
    grupo: 'Tienda',
    descripcion: 'STUB mapeado: registra que un pedido receptado requiere producción por falta de stock. La creación real de la orden de producción se implementará con el módulo Producción.',
    tipo: 'action',
)]
final class GenerarOrdenProduccionPedido
{
    /**
     * @param array<int, array{producto:string, cantidad:float, motivo:string}> $faltantes
     */
    public function handle(StoreOrder $order, array $faltantes): void
    {
        // TODO(Producción): crear ProductionOrder por cada faltante y enlazar al pedido.
        Log::info('Pedido receptado requiere producción (mapeado, sin implementar)', [
            'empresa_id' => $order->empresa_id,
            'pedido'     => $order->numero,
            'faltantes'  => $faltantes,
        ]);
    }
}
