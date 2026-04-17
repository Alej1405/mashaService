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
        Schema::create('logistics_billing_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('logistics_packages')->cascadeOnDelete();
            $table->foreignId('store_customer_id')->constrained('store_customers')->cascadeOnDelete();

            $table->string('numero_nota_venta')->unique();
            $table->string('token', 64)->unique();

            // Montos al momento de emisión
            $table->decimal('subtotal_0',  10, 2)->default(0);   // base 0% IVA
            $table->decimal('subtotal_15', 10, 2)->default(0);   // base 15% IVA
            $table->decimal('iva',         10, 2)->default(0);
            $table->decimal('total',       10, 2)->default(0);

            $table->json('items');  // líneas de detalle

            // Datos de facturación (se llenan cuando el cliente acepta)
            $table->enum('billing_type', ['customer', 'company'])->nullable();
            $table->foreignId('billing_company_id')
                  ->nullable()
                  ->constrained('store_customer_companies')
                  ->nullOnDelete();
            $table->string('billing_nombre')->nullable();
            $table->string('billing_ruc')->nullable();
            $table->string('billing_direccion')->nullable();

            // Estado del flujo
            $table->enum('estado', ['pendiente', 'aceptado', 'rechazado', 'facturado'])
                  ->default('pendiente');
            $table->enum('accepted_channel', ['email', 'portal', 'erp'])->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->text('notas')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistics_billing_requests');
    }
};
