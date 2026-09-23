<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de la Superintendencia de Compañías.
 *
 * Los cuatro archivos que se suben al portal llevan el código del catálogo de
 * la SCVS (1, 101, 10101…), no el del plan de cuentas de la empresa. Este es
 * ese catálogo, con el orden exacto de los TXT oficiales y los nombres del
 * PLAN_CUENTAS del organismo.
 *
 * Cada cuenta de la empresa apunta a su código aquí: eso es lo que permite
 * generar el archivo sin armarlo a mano cada abril.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_supercias', function (Blueprint $table) {
            $table->id();
            $table->string('estado', 30);            // situacion_financiera, resultado_integral…
            $table->string('columna', 10)->nullable(); // solo en cambios en el patrimonio
            $table->string('codigo', 12);
            $table->string('nombre', 200)->nullable();
            $table->string('signo', 10)->nullable();
            $table->unsignedSmallInteger('orden');
            $table->unsignedTinyInteger('nivel');
            $table->timestamps();
            $table->index(['estado', 'orden']);
            $table->index('codigo');
        });

        // El plan de cuentas apunta al catálogo por código.
        Schema::table('account_plans', function (Blueprint $table) {
            $table->string('codigo_supercias', 12)->nullable()->after('linea_estado');
            $table->index('codigo_supercias');
        });

        $archivo = database_path('data/catalogo_supercias.json');

        if (! file_exists($archivo)) {
            return;
        }

        foreach (array_chunk(json_decode(file_get_contents($archivo), true), 300) as $lote) {
            DB::table('catalogo_supercias')->insert(array_map(fn (array $f) => [
                'estado'     => $f['estado'],
                'columna'    => $f['columna'],
                'codigo'     => $f['codigo'],
                'nombre'     => $f['nombre'],
                'signo'      => $f['signo'],
                'orden'      => $f['orden'],
                'nivel'      => $f['nivel'],
                'created_at' => now(),
                'updated_at' => now(),
            ], $lote));
        }
    }

    public function down(): void
    {
        Schema::table('account_plans', function (Blueprint $table) {
            $table->dropIndex(['codigo_supercias']);
            $table->dropColumn('codigo_supercias');
        });

        Schema::dropIfExists('catalogo_supercias');
    }
};
