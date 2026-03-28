<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('ubicacion_almacen_id')
                ->nullable()
                ->after('foto_path')
                ->constrained('ubicaciones_almacen')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['ubicacion_almacen_id']);
            $table->dropColumn('ubicacion_almacen_id');
        });
    }
};
