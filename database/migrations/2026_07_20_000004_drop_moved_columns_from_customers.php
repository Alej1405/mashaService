<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Cierre de la normalización de `customers`: se eliminan las columnas ya migradas a sus
 * tablas de contexto (customer_finance, customer_export, customer_access). El código ya
 * lee vía accessors-puente y escribe en el contexto. `customers` queda angosto:
 * identidad fiscal + contacto + handle de punto de venta.
 */
return new class extends Migration
{
    private array $columns = [
        'cuenta_contable_id',   // → customer_finance
        'es_exportador',        // → customer_export
        'pais_destino',         // → customer_export
        'password',             // → customer_access
        'email_verified_at',    // → customer_access
        'is_super_admin',       // → customer_access
    ];

    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn($this->columns);
        });
    }

    public function down(): void
    {
        // Recrea las columnas vacías (sin restaurar datos: viven en las tablas de
        // contexto). Solo para que el rollback no falle.
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('cuenta_contable_id')->nullable();
            $table->boolean('es_exportador')->default(false);
            $table->string('pais_destino', 100)->nullable();
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_super_admin')->default(false);
        });
    }
};
