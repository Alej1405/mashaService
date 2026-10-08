<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compras que llegan como factura electrónica (correo y, después, foto por Telegram).
 *
 * - buzones_facturas: el correo al que el SRI y los proveedores mandan las facturas
 *   de cada empresa. n8n lo lee con su propio flujo y su credencial IMAP.
 * - productos_proveedor: qué es cada código de producto de un proveedor en esta
 *   empresa (un ítem de inventario o un tipo de gasto). La primera vez se configura a mano;
 *   desde ahí las compras de ese producto entran solas.
 * - purchases / purchase_items: de dónde vino la compra y lo que dice el XML.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buzones_facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->unique()->constrained('empresas')->cascadeOnDelete();
            $table->string('host');
            $table->unsignedSmallInteger('puerto')->default(993);
            $table->string('usuario');
            $table->text('password');                       // cifrada (cast encrypted)
            $table->boolean('activo')->default(true);
            // LOPDP: leer el correo de la empresa exige su consentimiento expreso.
            $table->foreignId('autorizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('autorizado_en')->nullable();
            $table->string('n8n_credencial_id')->nullable();
            $table->string('n8n_flujo_id')->nullable();
            $table->timestamp('sincronizado_en')->nullable();
            $table->text('ultimo_error')->nullable();
            $table->timestamps();
        });

        Schema::create('productos_proveedor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('codigo', 100);
            $table->string('descripcion', 300);
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            // Si no es inventario es un gasto: el tipo de gasto resuelve la cuenta de cada empresa.
            $table->foreignId('tipo_gasto_id')->nullable()->constrained('tipos_gasto')->nullOnDelete();
            $table->decimal('ultimo_precio', 16, 6)->nullable();
            $table->timestamp('configurado_en')->nullable();
            $table->foreignId('configurado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['empresa_id', 'supplier_id', 'codigo']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('origen', 20)->default('manual');       // manual | correo | telegram
            $table->string('clave_acceso', 49)->nullable();
            $table->string('xml_path')->nullable();
            $table->string('forma_pago_sri', 2)->nullable();        // Tabla 24 de la ficha del SRI
            $table->unsignedInteger('plazo_dias')->nullable();
            $table->boolean('requiere_forma_pago')->default(false);

            $table->unique(['empresa_id', 'clave_acceso']);
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->string('descripcion', 300)->nullable();
            $table->string('codigo_proveedor', 100)->nullable();
            $table->foreignId('producto_proveedor_id')->nullable()->constrained('productos_proveedor')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('producto_proveedor_id');
            $table->dropColumn(['descripcion', 'codigo_proveedor']);
        });
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropUnique(['empresa_id', 'clave_acceso']);
            $table->dropColumn(['origen', 'clave_acceso', 'xml_path', 'forma_pago_sri', 'plazo_dias', 'requiere_forma_pago']);
        });
        Schema::dropIfExists('productos_proveedor');
        Schema::dropIfExists('buzones_facturas');
    }
};
