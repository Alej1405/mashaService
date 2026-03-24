<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('store_customer_id')->constrained('store_customers')->cascadeOnDelete();
            $table->string('numero')->unique();
            $table->enum('estado', ['pendiente', 'pagado', 'procesando', 'enviado', 'entregado', 'cancelado'])->default('pendiente');
            $table->decimal('subtotal', 14, 4);
            $table->decimal('descuento', 14, 4)->default(0);
            $table->decimal('total', 14, 4);
            $table->foreignId('store_coupon_id')->nullable()->constrained('store_coupons')->nullOnDelete();
            $table->string('metodo_pago')->nullable();
            $table->enum('estado_pago', ['pendiente', 'aprobado', 'fallido', 'reembolsado'])->default('pendiente');
            $table->string('referencia_pago')->nullable();
            $table->json('direccion_envio');
            $table->text('notas_cliente')->nullable();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_orders');
    }
};
