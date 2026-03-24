<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_simulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('product_design_id')->constrained('product_designs')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('presentation_nombre')->nullable();
            $table->decimal('cantidad', 12, 2)->default(0);
            $table->decimal('pvp_sin_iva', 12, 4)->default(0);
            $table->decimal('margen_porcentaje', 8, 2)->default(0);
            $table->integer('dias_venta')->default(0);
            $table->decimal('meta_ganancia', 8, 2)->default(5);
            $table->boolean('aplica_ice')->default(false);
            $table->string('ice_categoria')->nullable();
            $table->decimal('ice_porcentaje', 8, 2)->default(0);
            // Resultados calculados
            $table->decimal('inversion_real', 14, 2)->default(0);
            $table->decimal('costo_total', 14, 2)->default(0);
            $table->decimal('ingreso_neto', 14, 2)->default(0);
            $table->decimal('utilidad_bruta', 14, 2)->default(0);
            $table->decimal('utilidad_neta', 14, 2)->default(0);
            $table->decimal('margen_bruto', 8, 2)->default(0);
            $table->decimal('margen_neto', 8, 2)->default(0);
            $table->decimal('roi', 8, 2)->default(0);
            $table->decimal('payback_dias', 8, 1)->nullable();
            $table->decimal('iva_total', 14, 2)->default(0);
            $table->decimal('ice_total', 14, 2)->default(0);
            $table->text('notas')->nullable();
            $table->string('estado')->default('borrador'); // borrador, aprobada, en_proyecto
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_simulations');
    }
};
