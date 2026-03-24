<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_customer_id')->constrained('store_customers')->cascadeOnDelete();
            $table->string('nombre_destinatario');
            $table->string('linea1');
            $table->string('linea2')->nullable();
            $table->string('ciudad');
            $table->string('provincia')->nullable();
            $table->string('pais')->default('Ecuador');
            $table->string('codigo_postal')->nullable();
            $table->string('telefono')->nullable();
            $table->boolean('es_principal')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_addresses');
    }
};
