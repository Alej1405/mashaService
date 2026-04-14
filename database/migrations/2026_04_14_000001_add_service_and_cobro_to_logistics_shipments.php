<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_shipments', function (Blueprint $table) {
            $table->foreignId('service_package_id')
                ->nullable()
                ->after('consignatario_id')
                ->constrained('service_packages')
                ->nullOnDelete();

            $table->decimal('monto_cobro', 12, 2)->nullable()->after('service_package_id');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_shipments', function (Blueprint $table) {
            $table->dropForeign(['service_package_id']);
            $table->dropColumn(['service_package_id', 'monto_cobro']);
        });
    }
};
