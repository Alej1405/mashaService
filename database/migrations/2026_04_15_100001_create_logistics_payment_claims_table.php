<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistics_payment_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('store_customer_id')->constrained('store_customers')->cascadeOnDelete();
            $table->json('package_ids');           // IDs de los paquetes incluidos
            $table->decimal('monto_declarado', 14, 2);
            $table->string('comprobante_path')->nullable();  // foto de la transferencia
            $table->text('notas_cliente')->nullable();
            $table->enum('estado', ['pendiente', 'verificado', 'rechazado'])->default('pendiente');
            $table->text('notas_verificador')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('verificado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verificado_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_payment_claims');
    }
};
