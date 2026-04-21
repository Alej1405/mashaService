<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_charge_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->decimal('monto', 10, 2);
            $table->string('tipo', 10)->default('tramite'); // tramite | peso
            $table->tinyInteger('iva_pct')->default(15);    // 0 | 15
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_charge_configs');
    }
};
