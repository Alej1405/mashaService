<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_shipment_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('shipment_id')->constrained('logistics_shipments')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();

            $table->string('descripcion');
            $table->string('numero_factura_proveedor')->nullable();
            $table->date('fecha_factura')->nullable();

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->unsignedTinyInteger('iva_pct')->default(15);
            $table->decimal('iva_monto', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            $table->enum('estado', ['por_pagar', 'pagada'])->default('por_pagar');
            $table->date('fecha_pago')->nullable();
            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_shipment_bills');
    }
};
