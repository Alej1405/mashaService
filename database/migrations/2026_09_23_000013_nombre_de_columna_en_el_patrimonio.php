<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El estado de cambios en el patrimonio es una matriz, no una lista.
 *
 * Sus 320 líneas son 20 conceptos patrimoniales × 16 movimientos. El nombre
 * oficial de cada línea viene como «SALDO AL FINAL DEL PERIODO / CAPITAL»:
 * antes de la barra el movimiento, después el concepto. Al cargar el catálogo
 * me quedé solo con la segunda parte, así que las dieciséis filas del código
 * 301 se veían idénticas y con el mismo valor.
 *
 * Aquí se recupera el nombre del movimiento, que es lo que convierte la lista
 * repetida en una tabla que se entiende.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalogo_supercias', function (Blueprint $table) {
            if (! Schema::hasColumn('catalogo_supercias', 'nombre_columna')) {
                $table->string('nombre_columna', 160)->nullable()->after('columna');
            }
        });

        $archivo = database_path('data/columnas_cambios_patrimonio.json');

        if (! file_exists($archivo)) {
            return;
        }

        foreach (json_decode(file_get_contents($archivo), true) as $columna => $nombre) {
            DB::table('catalogo_supercias')
                ->where('estado', 'cambios_patrimonio')
                ->where('columna', (string) $columna)
                ->update(['nombre_columna' => $nombre]);
        }
    }

    public function down(): void
    {
        Schema::table('catalogo_supercias', function (Blueprint $table) {
            $table->dropColumn('nombre_columna');
        });
    }
};
