<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nuevo estado de pedido: 'receptado' (la tienda acepta el pedido). Al recibirlo,
 * el StoreOrderObserver descuenta inventario automáticamente y mapea producción
 * para lo que falte. Se amplía el CHECK de store_orders.estado.
 */
return new class extends Migration
{
    private array $conReceptado = ['pendiente', 'pagado', 'receptado', 'procesando', 'enviado', 'entregado', 'cancelado'];
    private array $sinReceptado = ['pendiente', 'pagado', 'procesando', 'enviado', 'entregado', 'cancelado'];

    public function up(): void
    {
        $this->reemplazarCheck($this->conReceptado);
    }

    public function down(): void
    {
        $this->reemplazarCheck($this->sinReceptado);
    }

    private function reemplazarCheck(array $estados): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $lista = "'" . implode("','", $estados) . "'";
        DB::statement('ALTER TABLE store_orders DROP CONSTRAINT IF EXISTS store_orders_estado_check');
        DB::statement("ALTER TABLE store_orders ADD CONSTRAINT store_orders_estado_check CHECK (estado::text = ANY (ARRAY[{$lista}]::text[]))");
    }
};
