<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_billing_requests', function (Blueprint $table) {
            $table->string('descuento_tipo', 20)->nullable()->after('notas');       // cliente_fijo | promocion | otro
            $table->string('descuento_descripcion')->nullable()->after('descuento_tipo');
            $table->decimal('descuento_monto', 10, 2)->default(0)->after('descuento_descripcion');
            $table->string('numero_factura', 20)->nullable()->after('numero_nota_venta');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_billing_requests', function (Blueprint $table) {
            $table->dropColumn(['descuento_tipo', 'descuento_descripcion', 'descuento_monto', 'numero_factura']);
        });
    }
};
