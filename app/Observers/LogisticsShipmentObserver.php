<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\LogisticsPackage;
use App\Models\LogisticsShipment;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cuando un embarque llega a "en_aduana" (arribo Ecuador / inicio aduana),
 * genera una Venta de servicio por cada paquete que tenga servicio asignado
 * y un cliente vinculado. El SaleObserver se encarga del asiento contable.
 */
class LogisticsShipmentObserver
{
    /** Estados que disparan la generación de facturas de servicio. */
    private const ESTADOS_FACTURACION = ['en_aduana', 'declaracion_transmitida'];

    public function updated(LogisticsShipment $shipment): void
    {
        if (! $shipment->isDirty('estado')) {
            return;
        }

        if (! in_array($shipment->estado, self::ESTADOS_FACTURACION)) {
            return;
        }

        // Solo facturar en la primera transición a estos estados
        $estadoAnterior = $shipment->getOriginal('estado');
        if (in_array($estadoAnterior, self::ESTADOS_FACTURACION)) {
            return; // ya pasó por aquí
        }

        try {
            $this->generarFacturasServicio($shipment);
        } catch (\Throwable $e) {
            Log::error('LogisticsShipmentObserver: error generando facturas de servicio', [
                'shipment_id' => $shipment->id,
                'estado'      => $shipment->estado,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);
        }
    }

    // ── Lógica de facturación ────────────────────────────────────────────────

    private function generarFacturasServicio(LogisticsShipment $shipment): void
    {
        // Paquetes del embarque que tengan servicio y cliente ERP
        $packages = $shipment->packages()
            ->with(['servicePackage.serviceDesign', 'storeCustomer.customer'])
            ->whereNotNull('service_package_id')
            ->whereNotNull('monto_cobro')
            ->get();

        if ($packages->isEmpty()) {
            return;
        }

        // Agrupar por customer_id para generar UNA factura por cliente
        $porCliente = $packages->groupBy(fn ($pkg) => $pkg->resolverCustomerErp()?->id);

        foreach ($porCliente as $customerId => $pkgsCliente) {
            if (! $customerId) {
                continue; // paquetes sin cliente ERP identificado
            }

            $customer = Customer::find($customerId);
            if (! $customer) {
                continue;
            }

            $this->crearVentaServicio($shipment, $customer, $pkgsCliente);
        }
    }

    private function crearVentaServicio(
        LogisticsShipment $shipment,
        Customer          $customer,
        \Illuminate\Support\Collection $packages,
    ): void {
        DB::transaction(function () use ($shipment, $customer, $packages) {
            // Crear la venta en estado "confirmado" para que el SaleObserver genere el asiento
            $sale = Sale::create([
                'empresa_id'   => $shipment->empresa_id,
                'customer_id'  => $customer->id,
                'fecha'        => now()->toDateString(),
                'tipo_venta'   => 'servicio',
                'tipo_operacion' => 'normal',
                'forma_pago'   => 'credito',
                'subtotal'     => 0,
                'iva'          => 0,
                'total'        => 0,
                'estado'       => 'borrador', // primero borrador para agregar items
                'notas'        => 'Generada automáticamente desde embarque ' . $shipment->numero_embarque
                                  . ' al estado: ' . (LogisticsShipment::ESTADOS[$shipment->estado]['label'] ?? $shipment->estado),
            ]);

            foreach ($packages as $pkg) {
                $svcPkg = $pkg->servicePackage;
                if (! $svcPkg) {
                    continue;
                }

                // Buscar o crear el InventoryItem de tipo "servicio" para este servicio logístico
                $inventoryItem = $this->resolverItemServicio(
                    $shipment->empresa_id,
                    $svcPkg->serviceDesign?->nombre ?? $svcPkg->nombre,
                    $svcPkg->serviceDesign?->id ?? 0,
                );

                SaleItem::create([
                    'sale_id'            => $sale->id,
                    'inventory_item_id'  => $inventoryItem?->id,
                    'descripcion_servicio' => $svcPkg->serviceDesign?->nombre . ' — ' . $svcPkg->nombre
                                             . ($pkg->numero_tracking ? ' [' . $pkg->numero_tracking . ']' : ''),
                    'tipo_item'          => 'servicio',
                    'cantidad'           => $pkg->cantidad_cobro ?? 1,
                    'precio_unitario'    => $pkg->cantidad_cobro > 0
                                            ? round((float) $pkg->monto_cobro / (float) $pkg->cantidad_cobro, 4)
                                            : (float) $pkg->monto_cobro,
                    'aplica_iva'         => false, // servicios de logística courier generalmente exentos
                    'subtotal'           => 0, // lo calcula SaleItem::boot saving
                    'iva_monto'          => 0,
                    'total'              => 0,
                ]);

                // Marcar el paquete con referencia a la venta generada
                $pkg->updateQuietly(['sale_id' => $sale->id]);
            }

            // Confirmar la venta → dispara SaleObserver → genera asiento contable
            $sale->update(['estado' => 'confirmado']);
        });
    }

    /**
     * Busca o crea un InventoryItem de tipo "servicio" para el catálogo de la empresa.
     * Así el asiento contable puede mapear correctamente la cuenta de ingresos de servicios.
     */
    private function resolverItemServicio(int $empresaId, string $nombre, int $serviceDesignId): ?InventoryItem
    {
        $codigo = 'SVC-LOG-' . str_pad($serviceDesignId, 4, '0', STR_PAD_LEFT);

        return InventoryItem::withoutGlobalScopes()
            ->firstOrCreate(
                ['empresa_id' => $empresaId, 'codigo' => $codigo],
                [
                    'empresa_id'  => $empresaId,
                    'nombre'      => $nombre,
                    'descripcion' => 'Servicio logístico — generado automáticamente',
                    'type'        => 'servicio',
                    'activo'      => true,
                    'stock_actual' => 0,
                    'stock_minimo' => 0,
                ]
            );
    }
}
