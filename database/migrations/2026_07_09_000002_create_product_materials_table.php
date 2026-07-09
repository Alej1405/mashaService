<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * product_materials — PIVOTE producto ↔ insumos/materia prima.
 * "De qué está hecho el producto". SOLO insumos y materia prima; NADA de
 * producción (pasos, órdenes, mano de obra) — eso es otro módulo, aparte.
 * El proveedor NO se repite aquí: viene de inventory_items.supplier_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('store_product_id')->constrained('store_products')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->decimal('cantidad', 15, 4);
            $table->foreignId('measurement_unit_id')->constrained('measurement_units')->restrictOnDelete();
            $table->string('notas')->nullable();
            $table->timestamps();

            // Un insumo no se repite en el mismo producto (unicidad por empresa).
            $table->unique(['empresa_id', 'store_product_id', 'inventory_item_id'], 'product_materials_unq');
            $table->index('empresa_id');
            $table->index('store_product_id');
        });

        // Invariante de dominio: la cantidad de insumo debe ser positiva.
        // CHECK vía SQL crudo (Postgres). SQLite (tests) no lo soporta por ALTER;
        // ahí la validación queda a nivel de app (minValue).
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE product_materials ADD CONSTRAINT product_materials_cantidad_positiva CHECK (cantidad > 0)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE product_materials DROP CONSTRAINT IF EXISTS product_materials_cantidad_positiva');
        }
        Schema::dropIfExists('product_materials');
    }
};
