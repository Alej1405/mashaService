<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('proveedores');
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('codigo');
            $table->string('nombre');
            $table->string('nombre_comercial')->nullable();
            $table->enum('tipo_persona', ['juridica', 'natural']);
            $table->enum('tipo_identificacion', ['ruc', 'cedula', 'pasaporte']);
            $table->string('numero_identificacion');
            $table->json('tipo_proveedor');
            $table->string('contacto_principal');
            $table->string('telefono_principal');
            $table->string('telefono_secundario')->nullable();
            $table->string('correo_principal');
            $table->string('correo_secundario')->nullable();
            $table->text('direccion')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('pais')->default('Ecuador');
            $table->boolean('es_importador')->default(false);
            $table->string('pais_origen')->nullable();
            $table->foreignId('cuenta_contable_id')->nullable()->constrained('account_plans')->nullOnDelete();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->unique(['empresa_id', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
