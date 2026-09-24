<?php

use App\Services\MapeoSuperciasService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ninguna cuenta se queda sin su línea del estado.
 *
 * Antes el sistema proponía y alguien tenía que emparejar noventa cuentas a
 * mano; en la práctica eso significa que no se hace y el balance sale
 * incompleto. Ahora asigna siempre y guarda con cuánta confianza, para que el
 * contador revise las dudosas en vez de teclearlas todas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('account_plans', 'codigo_supercias_confianza')) {
                // 100 = lo puso una persona; menos = lo dedujo el sistema
                $table->unsignedTinyInteger('codigo_supercias_confianza')->nullable()
                    ->after('codigo_supercias');
            }

            if (! Schema::hasColumn('account_plans', 'codigo_supercias_revisado')) {
                $table->boolean('codigo_supercias_revisado')->default(false)
                    ->after('codigo_supercias_confianza');
            }
        });

        // Lo que ya estaba mapeado se da por bueno: alguien lo decidió.
        DB::table('account_plans')->whereNotNull('codigo_supercias')
            ->update(['codigo_supercias_confianza' => 100, 'codigo_supercias_revisado' => true]);

        // Y lo que falta se asigna ahora, empresa por empresa.
        $servicio = app(MapeoSuperciasService::class);

        foreach (DB::table('empresas')->pluck('id') as $empresaId) {
            $pendientes = DB::table('account_plans')->where('empresa_id', $empresaId)
                ->where('accepts_movements', true)->whereNull('codigo_supercias')->count();

            if ($pendientes > 0) {
                $servicio->mapearTodo((int) $empresaId);
            }
        }
    }

    public function down(): void
    {
        Schema::table('account_plans', function (Blueprint $table) {
            $table->dropColumn(['codigo_supercias_confianza', 'codigo_supercias_revisado']);
        });
    }
};
