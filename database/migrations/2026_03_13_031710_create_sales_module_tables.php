<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $blueprint->string('codigo')->index();
            $blueprint->string('nombre');
            $blueprint->enum('tipo_persona', ['natural', 'juridica'])->default('natural');
            $blueprint->enum('tipo_identificacion', ['cedula', 'ruc', 'pasaporte', 'consumidor_final'])->default('ruc');
            $blueprint->string('numero_identificacion')->index();
            $blueprint->string('email')->nullable();
            $blueprint->string('telefono')->nullable();
            $blueprint->text('direccion')->nullable();
            $blueprint->boolean('es_exportador')->default(false);
            $blueprint->string('pais_destino')->nullable();
            $blueprint->foreignId('cuenta_contable_id')->nullable()->constrained('account_plans')->nullOnDelete();
            $blueprint->boolean('activo')->default(true);
            $blueprint->timestamps();
            
            $blueprint->unique(['empresa_id', 'codigo']);
            $blueprint->unique(['empresa_id', 'numero_identificacion']);
        });

        Schema::create('sales', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $blueprint->string('referencia')->index();
            $blueprint->date('fecha');
            $blueprint->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $blueprint->enum('tipo_venta', ['contado', 'credito'])->default('contado');
            $blueprint->date('fecha_vencimiento')->nullable();
            $blueprint->enum('tipo_operacion', ['productos', 'servicios', 'mixta', 'exportacion'])->default('productos');
            $blueprint->decimal('subtotal', 14, 2)->default(0);
            $blueprint->decimal('iva', 14, 2)->default(0);
            $blueprint->decimal('total', 14, 2)->default(0);
            $blueprint->enum('estado', ['borrador', 'confirmado', 'anulado'])->default('borrador');
            $blueprint->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $blueprint->text('notas')->nullable();
            $blueprint->foreignId('confirmado_por')->nullable()->constrained('users')->nullOnDelete();
            $blueprint->timestamp('confirmado_at')->nullable();
            $blueprint->string('factura_electronica_id')->nullable();
            $blueprint->string('clave_acceso')->nullable();
            $blueprint->timestamps();

            $blueprint->unique(['empresa_id', 'referencia']);
        });

        Schema::create('sale_items', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $blueprint->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->restrictOnDelete();
            $blueprint->string('descripcion_servicio')->nullable();
            $blueprint->enum('tipo_item', ['producto', 'servicio'])->default('producto');
            $blueprint->decimal('cantidad', 10, 4);
            $blueprint->decimal('precio_unitario', 14, 4);
            $blueprint->boolean('aplica_iva')->default(true);
            $blueprint->decimal('subtotal', 14, 2);
            $blueprint->decimal('iva_monto', 14, 2)->default(0);
            $blueprint->decimal('total', 14, 2);
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('customers');
    }
};
