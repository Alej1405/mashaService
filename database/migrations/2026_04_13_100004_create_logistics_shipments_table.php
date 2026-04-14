<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('bodega_id')->constrained('logistics_bodegas')->cascadeOnDelete();
            $table->foreignId('consignatario_id')->constrained('logistics_consignatarios')->cascadeOnDelete();
            $table->string('numero_embarque')->unique();
            // Tipo: individual | consolidado | fraccionado
            $table->string('tipo')->default('individual');
            // Estados pre-embarque y aduana SENAE
            $table->string('estado')->default('carga_registrada');
            $table->date('fecha_embarque')->nullable();
            $table->date('fecha_llegada_ecuador')->nullable();
            $table->string('numero_declaracion_aduana')->nullable();
            $table->string('numero_guia_aerea')->nullable();
            $table->decimal('valor_total_declarado', 12, 2)->default(0);
            $table->decimal('peso_total_kg', 8, 3)->nullable();
            $table->decimal('impuestos_pagados', 10, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_shipments');
    }
};
