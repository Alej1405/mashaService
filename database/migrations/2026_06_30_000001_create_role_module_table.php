<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Capa de ROL en la visibilidad: qué módulos puede ver cada rol.
 *
 * Cada fila asocia un rol (Spatie) con una clave de módulo del catálogo
 * config('erp_features'). El super_admin crea los roles y marca sus módulos
 * desde el admin; la empresa solo asigna el rol a sus usuarios.
 *
 * Relación por ID (role_id), nunca por nombre: renombrar un rol no rompe nada.
 * Los módulos siempre funcionan en segundo plano (Observers, AccountingService);
 * esto solo controla la VISIBILIDAD en la navegación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('module_key');   // finanzas, tesoreria, compras, inventario, ventas, produccion, marketing, tienda, logistica
            $table->timestamps();

            $table->unique(['role_id', 'module_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_module');
    }
};
