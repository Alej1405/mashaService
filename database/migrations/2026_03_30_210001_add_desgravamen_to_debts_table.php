<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('debts', 'seguro_desgravamen_anual')) {
            return;
        }

        Schema::table('debts', function (Blueprint $table) {
            $table->decimal('seguro_desgravamen_anual', 8, 4)
                ->default(0)
                ->after('tasa_interes')
                ->comment('Tasa anual nominal del seguro de desgravamen (%). Ej: 0.35');
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->dropColumn('seguro_desgravamen_anual');
        });
    }
};
