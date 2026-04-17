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
        Schema::table('logistics_packages', function (Blueprint $table) {
            $table->decimal('impuestos_aduana', 10, 2)->nullable()->after('impuestos_paga_empresa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_packages', function (Blueprint $table) {
            $table->dropColumn('impuestos_aduana');
        });
    }
};
