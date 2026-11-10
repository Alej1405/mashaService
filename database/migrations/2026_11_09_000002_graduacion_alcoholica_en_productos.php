<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La graduación alcohólica es de la presentación: Ron Dorado 375 ml a 38° y
 * 750 ml a 40° son dos presentaciones del mismo producto. En % vol; vacía si
 * no aplica. Indexada porque la tienda filtra por ella.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_presentations', function (Blueprint $table) {
            $table->decimal('graduacion_alcoholica', 5, 2)->nullable()->after('capacidad');
            $table->index('graduacion_alcoholica');
        });

        DB::statement('ALTER TABLE product_presentations ADD CONSTRAINT product_presentations_graduacion_check
            CHECK (graduacion_alcoholica IS NULL OR graduacion_alcoholica BETWEEN 0 AND 100)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE product_presentations DROP CONSTRAINT IF EXISTS product_presentations_graduacion_check');

        Schema::table('product_presentations', function (Blueprint $table) {
            $table->dropIndex(['graduacion_alcoholica']);
            $table->dropColumn('graduacion_alcoholica');
        });
    }
};
