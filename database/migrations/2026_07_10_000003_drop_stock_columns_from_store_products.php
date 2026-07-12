<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Normalización: el stock de un producto de tienda deja de vivir en store_products
 * y pasa a leerse del inventario real vía store_product_stock → inventory_items.
 *
 * Se retiran las columnas duplicadas (una sola fuente de verdad = inventory_items):
 *   - stock, stock_minimo  → los reemplaza inventory_items.stock_actual / stock_minimo
 *   - gestionar_stock      → un producto "gestiona stock" si tiene items enlazados
 *
 * El modelo StoreProduct expone `stock` / `gestionar_stock` como accessors virtuales
 * derivados de la relación, así que las lecturas existentes siguen funcionando.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            foreach (['stock', 'stock_minimo', 'gestionar_stock'] as $col) {
                if (Schema::hasColumn('store_products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            if (! Schema::hasColumn('store_products', 'stock')) {
                $table->integer('stock')->default(0);
            }
            if (! Schema::hasColumn('store_products', 'stock_minimo')) {
                $table->integer('stock_minimo')->default(0);
            }
            if (! Schema::hasColumn('store_products', 'gestionar_stock')) {
                $table->boolean('gestionar_stock')->default(true);
            }
        });
    }
};
