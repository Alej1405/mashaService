<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('bodega_id')->constrained('logistics_bodegas')->cascadeOnDelete();
            $table->foreignId('consignatario_id')->nullable()->constrained('logistics_consignatarios')->nullOnDelete();
            $table->string('numero_tracking')->nullable();
            $table->string('referencia')->nullable();   // referencia interna
            $table->string('descripcion');
            $table->decimal('peso_kg', 8, 3)->nullable();
            $table->decimal('largo_cm', 7, 2)->nullable();
            $table->decimal('ancho_cm', 7, 2)->nullable();
            $table->decimal('alto_cm', 7, 2)->nullable();
            $table->decimal('valor_declarado', 10, 2)->default(0);
            $table->string('moneda', 3)->default('USD');
            $table->string('estado')->default('registrado'); // registrado | en_bodega | asignado | entregado
            $table->text('notas')->nullable();
            $table->date('fecha_recepcion_bodega')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_packages');
    }
};
