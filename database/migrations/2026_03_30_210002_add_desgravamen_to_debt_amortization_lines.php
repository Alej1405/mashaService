<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('debt_amortization_lines', 'seguro_desgravamen')) {
            return;
        }

        Schema::table('debt_amortization_lines', function (Blueprint $table) {
            $table->decimal('seguro_desgravamen', 12, 2)
                ->default(0)
                ->after('monto_interes')
                ->comment('Monto del seguro de desgravamen de esta cuota');
        });
    }

    public function down(): void
    {
        Schema::table('debt_amortization_lines', function (Blueprint $table) {
            $table->dropColumn('seguro_desgravamen');
        });
    }
};
