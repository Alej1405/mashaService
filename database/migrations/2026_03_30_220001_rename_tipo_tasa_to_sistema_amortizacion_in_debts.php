<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Si la columna todavía se llama tipo_tasa, renombrarla primero
        if (Schema::hasColumn('debts', 'tipo_tasa')) {
            Schema::table('debts', function (Blueprint $table) {
                $table->renameColumn('tipo_tasa', 'sistema_amortizacion');
            });
        }

        // Nota: la columna ya fue renombrada de tipo_tasa a sistema_amortizacion
        // en un intento previo. Solo cambiamos el tipo y los datos.
        Schema::table('debts', function (Blueprint $table) {
            $table->string('sistema_amortizacion', 20)->default('frances')->change();
        });

        // Mapear valores anteriores al nuevo esquema
        DB::statement("UPDATE debts SET sistema_amortizacion = 'frances'
                        WHERE sistema_amortizacion IN ('compuesto', 'simple')");

        Schema::table('debts', function (Blueprint $table) {
            $table->enum('sistema_amortizacion', ['frances', 'aleman', 'americano'])->default('frances')->change();
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->string('sistema_amortizacion', 20)->default('frances')->change();
        });

        DB::statement("UPDATE debts SET sistema_amortizacion = 'compuesto'
                        WHERE sistema_amortizacion IN ('frances', 'aleman', 'americano')");

        Schema::table('debts', function (Blueprint $table) {
            $table->renameColumn('sistema_amortizacion', 'tipo_tasa');
        });

        Schema::table('debts', function (Blueprint $table) {
            $table->enum('tipo_tasa', ['simple', 'compuesto'])->default('simple')->change();
        });
    }
};
