<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("purchases", function (Blueprint $table) {
            if (!Schema::hasColumn("purchases", "numero_factura")) {
                $table->string("numero_factura", 100)->nullable()->after("number");
            }
        });
    }

    public function down(): void
    {
        Schema::table("purchases", function (Blueprint $table) {
            if (Schema::hasColumn("purchases", "numero_factura")) {
                $table->dropColumn("numero_factura");
            }
        });
    }
};
