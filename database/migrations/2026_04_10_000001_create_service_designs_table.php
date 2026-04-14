<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('categoria')->nullable();
            $table->text('descripcion_servicio')->nullable();
            $table->longText('propuesta_valor')->nullable();
            $table->text('notas_estrategicas')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('tiene_multiples_paquetes')->default(false);
            $table->string('unidad_capacidad')->default('sesion'); // sesion, hora, cliente, proyecto
            $table->decimal('capacidad_mensual', 10, 2)->nullable();
            $table->integer('dias_laborales_mes')->default(22);
            $table->decimal('num_personas', 8, 2)->nullable();
            $table->decimal('costo_persona_mes', 12, 2)->nullable();
            $table->decimal('precio_revendedor', 12, 4)->nullable();
            $table->decimal('margen_revendedor', 8, 2)->nullable();
            $table->integer('cantidad_minima_revendedor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_designs');
    }
};
