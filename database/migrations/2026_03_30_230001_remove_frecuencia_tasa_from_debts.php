<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->dropColumn('frecuencia_tasa');
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->enum('frecuencia_tasa', ['mensual', 'anual'])->default('anual')->after('sistema_amortizacion');
        });
    }
};
