<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('store_category_id')->nullable()->constrained('store_categories')->nullOnDelete();
            $table->string('nombre');
            $table->string('slug');
            $table->text('descripcion')->nullable();
            $table->decimal('precio_venta', 10, 4);
            $table->string('imagen_principal')->nullable();
            $table->json('galeria')->nullable();
            $table->boolean('publicado')->default(false);
            $table->boolean('destacado')->default(false);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['empresa_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_products');
    }
};
