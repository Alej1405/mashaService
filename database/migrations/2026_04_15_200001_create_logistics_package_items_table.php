<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_package_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_package_id')
                  ->constrained('logistics_packages')
                  ->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('valor', 10, 2)->nullable();
            $table->string('foto_path')->nullable();
            $table->decimal('gastos_envio', 10, 2)->nullable();
            $table->decimal('impuestos_amazon', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_package_items');
    }
};
