<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Un pedido es una sola orden de producción; sus productos son líneas de esa
 * orden, cada una con su cantidad y su lote de entrega. La orden de un pedido no
 * tiene un producto terminado único: su inventory_item_id queda vacío.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE production_orders ALTER COLUMN inventory_item_id DROP NOT NULL');

        Schema::create('production_order_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('store_order_item_id')->nullable()->constrained('store_order_items')->nullOnDelete();
            $table->foreignId('product_presentation_id')->nullable()->constrained('product_presentations')->nullOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            $table->string('descripcion', 255);
            $table->decimal('cantidad', 14, 4);
            $table->string('lote', 60)->nullable();
            $table->timestamps();
            $table->unique(['production_order_id', 'store_order_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_lineas');
    }
};
