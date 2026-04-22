<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_shipment_bills', function (Blueprint $table) {
            $table->string('factura_pdf_path')->nullable()->after('fecha_factura');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_shipment_bills', function (Blueprint $table) {
            $table->dropColumn('factura_pdf_path');
        });
    }
};
