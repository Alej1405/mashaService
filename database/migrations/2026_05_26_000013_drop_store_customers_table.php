<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paso 4/4: Elimina store_customers.
 * Solo se ejecuta cuando los 3 pasos anteriores completaron sin errores.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Primero eliminar el FK que apunta a customers desde store_customers
        Schema::table('store_customers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });

        Schema::dropIfExists('store_customers');
    }

    public function down(): void
    {
        // No se puede recrear sin datos — restaurar desde backup.
    }
};
