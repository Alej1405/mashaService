<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menú del punto de venta (cliente). Tabla INDEPENDIENTE colgada del cliente para no
 * mezclar con su identidad ni con el catálogo de la tienda. Cada ítem es un producto
 * del menú con su detalle y precio. Se muestra en la web solo si el cliente tiene
 * `menu_activo = true` (ver customers).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();   // "detalles" del producto
            $table->decimal('precio', 12, 2);
            $table->string('imagen')->nullable();
            $table->smallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('empresa_id');
            $table->index('customer_id');
        });

        // Invariante de dominio: el precio nunca es negativo.
        DB::statement('ALTER TABLE customer_menu_items ADD CONSTRAINT customer_menu_items_precio_no_negativo CHECK (precio >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE customer_menu_items DROP CONSTRAINT IF EXISTS customer_menu_items_precio_no_negativo');
        Schema::dropIfExists('customer_menu_items');
    }
};
