<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Generaliza measurement_units: cada unidad pertenece a una familia (tipo) y tiene
 * un `factor` = cuántas unidades base de su familia equivale. Así el sistema
 * convierte SOLO entre unidades compatibles (1 L = 1000 mL) sin cuentas manuales.
 *
 * Bases por familia (factor 1): volumen=mililitro, longitud=metro, masa=gramo,
 * conteo=unidad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('measurement_units', function (Blueprint $table) {
            $table->string('tipo')->nullable()->after('abreviatura');
            $table->decimal('factor', 20, 8)->default(1)->after('tipo');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE measurement_units ADD CONSTRAINT measurement_units_tipo_check CHECK (tipo IS NULL OR tipo IN ('volumen','longitud','masa','conteo'))");
            DB::statement('ALTER TABLE measurement_units ADD CONSTRAINT measurement_units_factor_positivo CHECK (factor > 0)');
        }

        // Mapear las unidades existentes a su familia + factor (por nombre).
        $map = [
            'unidad' => ['conteo', 1], 'unidades' => ['conteo', 1], 'u' => ['conteo', 1],
            'docena' => ['conteo', 12], 'par' => ['conteo', 2], 'ciento' => ['conteo', 100],
            'caja 12' => ['conteo', 12], 'cajas 12' => ['conteo', 12], 'caja24' => ['conteo', 24], 'cajas24' => ['conteo', 24], 'caja 24' => ['conteo', 24], 'cajas 24' => ['conteo', 24],
            'mililitro' => ['volumen', 1], 'mililitros' => ['volumen', 1],
            'litro' => ['volumen', 1000], 'litros' => ['volumen', 1000],
            'galon' => ['volumen', 3785.41], 'galón' => ['volumen', 3785.41],
            'metro' => ['longitud', 1], 'metros' => ['longitud', 1],
            'centimetro' => ['longitud', 0.01], 'centimetros' => ['longitud', 0.01], 'centímetro' => ['longitud', 0.01],
            'milimetro' => ['longitud', 0.001], 'milímetro' => ['longitud', 0.001],
            'kilometro' => ['longitud', 1000], 'kilómetro' => ['longitud', 1000],
            'gramo' => ['masa', 1], 'gramos' => ['masa', 1],
            'kilo' => ['masa', 1000], 'kilos' => ['masa', 1000], 'kilogramo' => ['masa', 1000], 'kilogramos' => ['masa', 1000],
            'miligramo' => ['masa', 0.001], 'miligramos' => ['masa', 0.001],
            'libra' => ['masa', 453.592], 'libras' => ['masa', 453.592],
            'tonelada' => ['masa', 1000000], 'toneladas' => ['masa', 1000000],
        ];
        foreach (DB::table('measurement_units')->get() as $u) {
            $key = mb_strtolower(trim($u->nombre));
            if (isset($map[$key])) {
                DB::table('measurement_units')->where('id', $u->id)->update([
                    'tipo'   => $map[$key][0],
                    'factor' => $map[$key][1],
                ]);
            }
        }

        // Asegurar unidades base de longitud (metro/cm) en cada empresa que ya use unidades.
        $empresas = DB::table('measurement_units')->distinct()->pluck('empresa_id');
        $base = [
            ['Metro', 'm', 'longitud', 1],
            ['Centímetro', 'cm', 'longitud', 0.01],
        ];
        foreach ($empresas as $eid) {
            foreach ($base as [$nombre, $abrev, $tipo, $factor]) {
                $existe = DB::table('measurement_units')
                    ->where('empresa_id', $eid)
                    ->whereRaw('lower(nombre) = ?', [mb_strtolower($nombre)])
                    ->exists();
                if (! $existe) {
                    DB::table('measurement_units')->insert([
                        'empresa_id'  => $eid,
                        'nombre'      => $nombre,
                        'abreviatura' => $abrev,
                        'tipo'        => $tipo,
                        'factor'      => $factor,
                        'activo'      => true,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE measurement_units DROP CONSTRAINT IF EXISTS measurement_units_tipo_check');
            DB::statement('ALTER TABLE measurement_units DROP CONSTRAINT IF EXISTS measurement_units_factor_positivo');
        }
        Schema::table('measurement_units', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'factor']);
        });
    }
};
