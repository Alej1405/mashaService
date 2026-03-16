<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('codigo');
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('type', ['insumo', 'materia_prima', 'producto_terminado', 'activo_fijo', 'servicio']);
            $table->foreignId('measurement_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('account_plan_id')->nullable()->constrained('account_plans')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->decimal('purchase_price', 10, 4)->default(0);
            $table->decimal('sale_price', 10, 4)->nullable();
            $table->decimal('stock_actual', 10, 4)->default(0);
            $table->decimal('stock_minimo', 10, 4)->default(0);
            $table->string('lote')->nullable();
            $table->date('fecha_caducidad')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->unique(['empresa_id', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
