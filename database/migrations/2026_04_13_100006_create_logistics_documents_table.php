<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            // Polimórfico: puede colgar de un embarque o de un paquete
            $table->morphs('documentable');
            // Tipos: declaracion_aduana | factura_producto | factura_servicio | foto | otro
            $table->string('tipo');
            $table->string('nombre');
            $table->string('archivo_path');
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_documents');
    }
};
