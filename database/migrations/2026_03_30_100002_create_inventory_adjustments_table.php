<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('item_presentation_id')->nullable()->constrained('item_presentations')->nullOnDelete();
            $table->decimal('cantidad_presentacion', 15, 6); // cantidad ingresada en la presentación elegida
            $table->decimal('factor_empaque', 15, 6)->default(1);
            $table->decimal('total_unidades_base', 15, 6);   // cantidad_presentacion * factor_empaque
            $table->string('tipo', 30);                      // 'entrada', 'salida', 'correccion'
            $table->decimal('stock_anterior', 15, 6)->default(0);
            $table->decimal('stock_nuevo', 15, 6)->default(0);
            $table->decimal('costo_unitario', 15, 6)->nullable(); // costo por unidad base
            $table->text('motivo')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
