<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modo «Cuadre de gestión anterior»: mientras está encendido, los meses anteriores
 * al inicio de gestión admiten cambios aunque estén cerrados. Se guarda quién lo
 * encendió y cuándo; apagado vale null.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->timestamp('modo_cuadre_en')->nullable();
            $table->foreignId('modo_cuadre_por')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('modo_cuadre_por');
            $table->dropColumn('modo_cuadre_en');
        });
    }
};
