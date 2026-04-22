<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_billing_requests', function (Blueprint $table) {
            $table->string('factura_comercial_path')->nullable()->after('numero_factura');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_billing_requests', function (Blueprint $table) {
            $table->dropColumn('factura_comercial_path');
        });
    }
};
