<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quitar los campos del embarque (se movieron al paquete)
        Schema::table('logistics_shipments', function (Blueprint $table) {
            $table->dropForeign(['service_package_id']);
            $table->dropColumn(['service_package_id', 'monto_cobro']);
        });

        // Agregar al paquete
        Schema::table('logistics_packages', function (Blueprint $table) {
            $table->foreignId('service_package_id')
                ->nullable()
                ->after('store_customer_id')
                ->constrained('service_packages')
                ->nullOnDelete();

            // Cantidad en la unidad que defina el servicio (lb, kg, unidades, etc.)
            $table->decimal('cantidad_cobro', 10, 4)->nullable()->after('service_package_id');
            $table->decimal('monto_cobro', 12, 2)->nullable()->after('cantidad_cobro');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_packages', function (Blueprint $table) {
            $table->dropForeign(['service_package_id']);
            $table->dropColumn(['service_package_id', 'cantidad_cobro', 'monto_cobro']);
        });

        Schema::table('logistics_shipments', function (Blueprint $table) {
            $table->foreignId('service_package_id')
                ->nullable()
                ->constrained('service_packages')
                ->nullOnDelete();
            $table->decimal('monto_cobro', 12, 2)->nullable();
        });
    }
};
