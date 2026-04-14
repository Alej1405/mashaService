<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_shipment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')
                  ->constrained('logistics_shipments')
                  ->cascadeOnDelete();
            $table->string('tipo');          // cambio_estado | nota | documento | paquete
            $table->string('estado_anterior')->nullable();
            $table->string('estado_nuevo')->nullable();
            $table->text('descripcion');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_nombre')->nullable(); // snapshot del nombre al momento
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_shipment_history');
    }
};
