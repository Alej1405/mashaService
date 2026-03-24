<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Nota: El precio de distribuidor se gestiona a nivel de Diseño de Producto,
// no de presentación. Se agrega aquí para mantener el orden cronológico.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_designs', function (Blueprint $table) {
            if (!Schema::hasColumn('product_designs', 'precio_distribuidor')) {
                $table->decimal('precio_distribuidor', 15, 4)->default(0)->after('inventory_item_id');
            }
            if (!Schema::hasColumn('product_designs', 'margen_distribuidor')) {
                $table->decimal('margen_distribuidor', 5, 2)->default(40)->after('precio_distribuidor');
            }
            if (!Schema::hasColumn('product_designs', 'cantidad_minima_distribuidor')) {
                $table->unsignedSmallInteger('cantidad_minima_distribuidor')->default(10)->after('margen_distribuidor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_designs', function (Blueprint $table) {
            $table->dropColumn(['precio_distribuidor', 'margen_distribuidor', 'cantidad_minima_distribuidor']);
        });
    }
};
