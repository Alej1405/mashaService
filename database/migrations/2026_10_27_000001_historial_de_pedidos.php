<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Cada paso del pedido queda con su fecha y quién lo dio, no solo el estado actual. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_order_historial', function (Blueprint $t) {
            $t->id();
            $t->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $t->foreignId('store_order_id')->constrained('store_orders')->cascadeOnDelete();
            $t->string('estado', 30);
            $t->timestampTz('ocurrido_en');
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('nota')->nullable();
            $t->timestampsTz();
            $t->index(['store_order_id', 'ocurrido_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_order_historial');
    }
};
