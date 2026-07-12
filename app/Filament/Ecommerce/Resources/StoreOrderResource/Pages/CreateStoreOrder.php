<?php

namespace App\Filament\Ecommerce\Resources\StoreOrderResource\Pages;

use App\Filament\Ecommerce\Resources\StoreOrderResource;
use App\Models\StoreOrder;
use App\Models\StoreProduct;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateStoreOrder extends CreateRecord
{
    protected static string $resource = StoreOrderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Ingresa un pedido desde el panel Tienda (origen = tienda). Es una solicitud
     * (a producir), por eso NO valida stock. Los estados/avance se reportan luego
     * desde la orden de trabajo (Producción). Envíos/pagos: módulos posteriores.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $empresa = Filament::getTenant();

        $items = collect($this->data['items'] ?? [])
            ->filter(fn ($i) => ! empty($i['store_product_id']) && (float) ($i['cantidad'] ?? 0) > 0)
            ->values();

        if ($items->isEmpty()) {
            throw new \RuntimeException('Agrega al menos un producto al pedido.');
        }

        return DB::transaction(function () use ($empresa, $data, $items) {
            $order = StoreOrder::create([
                'empresa_id'      => $empresa->id,
                'customer_id'     => $data['customer_id'],
                'origen'          => 'tienda',
                'estado'          => 'pendiente',
                'subtotal'        => 0,
                'descuento'       => 0,
                'total'           => 0,
                'estado_pago'     => 'pendiente',
                'direccion_envio' => [],
                'notas_cliente'   => $data['notas_cliente'] ?? null,
            ]);

            $subtotal = 0.0;
            foreach ($items as $it) {
                $producto = StoreProduct::find($it['store_product_id']);
                if (! $producto) {
                    continue;
                }
                $precio = (float) $producto->precio_venta;
                $cant   = (float) $it['cantidad'];
                $linea  = $precio * $cant;
                $subtotal += $linea;

                $order->orderItems()->create([
                    'store_product_id'  => $producto->id,
                    'inventory_item_id' => null,
                    'nombre_snapshot'   => $producto->nombre,
                    'precio_unitario'   => $precio,
                    'cantidad'          => $cant,
                    'subtotal'          => $linea,
                ]);
            }

            $order->update(['subtotal' => $subtotal, 'total' => $subtotal]);

            return $order->fresh();
        });
    }
}
