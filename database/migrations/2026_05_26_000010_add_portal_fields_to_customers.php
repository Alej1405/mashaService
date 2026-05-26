<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paso 1/4: Agrega los campos de portal (antes en store_customers) a la tabla customers.
 * No toca datos existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('apellido')->nullable()->after('nombre');
            $table->string('razon_social')->nullable()->after('apellido');
            $table->string('password')->nullable()->after('email');
            $table->timestamp('email_verified_at')->nullable()->after('password');
            $table->boolean('is_super_admin')->default(false)->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['apellido', 'razon_social', 'password', 'email_verified_at', 'is_super_admin']);
        });
    }
};
