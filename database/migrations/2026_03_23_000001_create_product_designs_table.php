<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('categoria')->nullable();
            $table->longText('propuesta_valor')->nullable();
            $table->text('notas_estrategicas')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('tiene_multiples_presentaciones')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_designs');
    }
};
