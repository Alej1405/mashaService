<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La fórmula es del producto y la presentación es lo que se vende.
 *
 * - El producto guarda una sola fórmula para su cantidad base (el pedido mínimo).
 * - Cada presentación lleva su envase, su capacidad en la unidad base, su código
 *   del SRI (con el que se reconoce en las facturas), su ítem de inventario y su
 *   producto de tienda.
 * - La tienda solo gana un vínculo opcional: en el plan basic queda vacío.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_designs', function (Blueprint $t) {
            $t->decimal('cantidad_base', 12, 4)->nullable();
            $t->foreignId('unidad_base_id')->nullable()->constrained('measurement_units')->nullOnDelete();
        });

        Schema::table('product_formula_lines', function (Blueprint $t) {
            $t->foreignId('product_design_id')->nullable()->constrained('product_designs')->cascadeOnDelete();
            $t->unsignedBigInteger('presentation_id')->nullable()->change();
        });

        Schema::table('product_presentations', function (Blueprint $t) {
            $t->string('codigo_sri', 50)->nullable()->index();
            $t->decimal('capacidad', 12, 4)->nullable();
            $t->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
        });

        Schema::table('store_products', function (Blueprint $t) {
            $t->foreignId('product_presentation_id')->nullable()->constrained('product_presentations')->nullOnDelete();
        });

        // Lo que ya estaba: la receta (materias primas) pasa al producto y el envase
        // (insumos) se queda en su presentación.
        DB::statement("
            update product_formula_lines f
               set product_design_id = p.product_design_id, presentation_id = null
              from product_presentations p, inventory_items i
             where p.id = f.presentation_id and i.id = f.inventory_item_id and i.type = 'materia_prima'
        ");

        // El ítem que ya produjo un diseño de una sola presentación es el de esa presentación.
        DB::statement("
            update product_presentations p
               set inventory_item_id = d.inventory_item_id
              from product_designs d
             where d.id = p.product_design_id and d.inventory_item_id is not null and p.inventory_item_id is null
               and (select count(*) from product_presentations x where x.product_design_id = d.id) = 1
        ");
    }

    public function down(): void
    {
        Schema::table('store_products', fn (Blueprint $t) => $t->dropConstrainedForeignId('product_presentation_id'));
        Schema::table('product_presentations', function (Blueprint $t) {
            $t->dropConstrainedForeignId('inventory_item_id');
            $t->dropColumn(['codigo_sri', 'capacidad']);
        });
        Schema::table('product_formula_lines', fn (Blueprint $t) => $t->dropConstrainedForeignId('product_design_id'));
        Schema::table('product_designs', function (Blueprint $t) {
            $t->dropConstrainedForeignId('unidad_base_id');
            $t->dropColumn('cantidad_base');
        });
    }
};
