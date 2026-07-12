<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * store_product_stock — PIVOTE producto de tienda ↔ inventario terminado.
 *
 * "De dónde sale el stock del producto". Un producto vendible se respalda con
 * uno o varios inventory_items (type=producto_terminado). El stock disponible NO
 * se guarda en store_products: se LEE de inventory_items.stock_actual a través de
 * esta relación. `cantidad` = cuántas unidades de inventario equivalen a 1 unidad
 * vendible (1 normalmente; >1 para packs/kits).
 *
 * Producción queda fuera: acá solo se lee/descuenta inventario existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_product_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('store_product_id')->constrained('store_products')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->decimal('cantidad', 15, 4)->default(1);
            $table->timestamps();

            // Un mismo item de inventario no se repite en el mismo producto (por empresa).
            $table->unique(['empresa_id', 'store_product_id', 'inventory_item_id'], 'store_product_stock_unq');
            $table->index('empresa_id');
            $table->index('store_product_id');
        });

        // Invariante de dominio: la equivalencia debe ser positiva.
        // CHECK vía SQL crudo (Postgres). SQLite (tests) no lo soporta por ALTER.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE store_product_stock ADD CONSTRAINT store_product_stock_cantidad_positiva CHECK (cantidad > 0)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE store_product_stock DROP CONSTRAINT IF EXISTS store_product_stock_cantidad_positiva');
        }
        Schema::dropIfExists('store_product_stock');
    }
};
