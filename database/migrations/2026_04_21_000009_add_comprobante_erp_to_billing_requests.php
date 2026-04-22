<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_billing_requests', function (Blueprint $table) {
            $table->string('comprobante_erp_path')->nullable()->after('factura_comercial_path');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_billing_requests', function (Blueprint $table) {
            $table->dropColumn('comprobante_erp_path');
        });
    }
};
