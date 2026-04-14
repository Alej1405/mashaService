<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cms_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 12, 4)->nullable();
            $table->string('unidad_precio')->nullable(); // por kg, por hora, por unidad, etc.
            $table->string('imagen')->nullable();
            $table->string('categoria')->nullable();
            $table->json('caracteristicas')->nullable();
            $table->string('icono')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_products');
    }
};
