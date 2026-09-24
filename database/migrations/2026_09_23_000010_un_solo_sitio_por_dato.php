<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Un dato, un sitio. Se elimina lo duplicado de raíz.
 *
 * 1. `account_plans.linea_estado` desaparece. Respondía exactamente a la misma
 *    pregunta que `codigo_supercias` —a qué línea del estado suma esta cuenta—
 *    y tener las dos hizo que el balance saliera en cero mientras el mapeo
 *    decía estar completo, y que tres pantallas mostraran tres números.
 *
 * 2. La tabla `activos_fijos` desaparece. El inventario ya clasifica sus ítems
 *    con `type = 'activo_fijo'`: crear otra tabla dejó una con un ítem real y
 *    la otra vacía, y la depreciación leyendo la vacía. Los datos contables que
 *    le faltaban al inventario se le añaden ahí.
 *
 * Marco: skill `contabilidad-ec` — un dato vive en un solo sitio.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1 · el activo fijo vive en el inventario, con lo que le faltaba ──
        Schema::table('inventory_items', function (Blueprint $table) {
            foreach ([
                'fecha_compra'           => fn () => $table->date('fecha_compra')->nullable(),
                'valor_residual'         => fn () => $table->decimal('valor_residual', 15, 2)->default(0),
                'vida_util_meses'        => fn () => $table->unsignedSmallInteger('vida_util_meses')->nullable(),
                'depreciacion_acumulada' => fn () => $table->decimal('depreciacion_acumulada', 15, 2)->default(0),
                'cuenta_depreciacion_id' => fn () => $table->unsignedBigInteger('cuenta_depreciacion_id')->nullable(),
                'cuenta_gasto_id'        => fn () => $table->unsignedBigInteger('cuenta_gasto_id')->nullable(),
            ] as $columna => $crear) {
                if (! Schema::hasColumn('inventory_items', $columna)) {
                    $crear();
                }
            }
        });

        // Lo que hubiera en la tabla vieja se muda antes de borrarla.
        if (Schema::hasTable('activos_fijos')) {
            foreach (DB::table('activos_fijos')->get() as $viejo) {
                $existe = DB::table('inventory_items')
                    ->where('empresa_id', $viejo->empresa_id)
                    ->where('nombre', $viejo->nombre)->first();

                $datos = [
                    'fecha_compra'           => $viejo->fecha_compra,
                    'valor_residual'         => $viejo->valor_residual,
                    'vida_util_meses'        => $viejo->vida_util_meses,
                    'depreciacion_acumulada' => $viejo->depreciacion_acumulada,
                    'cuenta_depreciacion_id' => $viejo->cuenta_depreciacion_id,
                    'cuenta_gasto_id'        => $viejo->cuenta_gasto_id,
                ];

                if ($existe) {
                    DB::table('inventory_items')->where('id', $existe->id)->update($datos);

                    continue;
                }

                DB::table('inventory_items')->insert($datos + [
                    'empresa_id'      => $viejo->empresa_id,
                    'codigo'          => $viejo->codigo ?: 'AF-' . $viejo->id,
                    'nombre'          => $viejo->nombre,
                    'type'            => 'activo_fijo',
                    'account_plan_id' => $viejo->cuenta_activo_id,
                    'purchase_price'  => $viejo->costo,
                    'costo_promedio'  => $viejo->costo,
                    'stock_actual'    => 1,
                    'saldo_valorado'  => $viejo->costo,
                    'activo'          => $viejo->activo,
                    'created_at'      => $viejo->created_at,
                    'updated_at'      => now(),
                ]);
            }
        }

        // La depreciación pasa a apuntar al ítem de inventario.
        if (Schema::hasTable('depreciaciones') && ! Schema::hasColumn('depreciaciones', 'inventory_item_id')) {
            Schema::table('depreciaciones', function (Blueprint $table) {
                $table->foreignId('inventory_item_id')->nullable()->after('id')
                    ->constrained('inventory_items')->cascadeOnDelete();
            });

            // Sin filas que migrar: la tabla vieja nunca llegó a usarse.
            Schema::table('depreciaciones', function (Blueprint $table) {
                $table->dropColumn('activo_fijo_id');
            });
        }

        Schema::dropIfExists('activos_fijos');

        // ── 2 · una sola columna para la línea del estado ────────────────────
        if (Schema::hasColumn('account_plans', 'linea_estado')) {
            // Lo que alguien hubiera escrito a mano no se pierde: si coincide
            // con un código del catálogo, se conserva como mapeo revisado.
            DB::statement("
                UPDATE account_plans SET codigo_supercias = linea_estado,
                       codigo_supercias_confianza = 100, codigo_supercias_revisado = true
                 WHERE codigo_supercias IS NULL
                   AND linea_estado IS NOT NULL
                   AND linea_estado IN (SELECT codigo FROM catalogo_supercias)
            ");

            Schema::table('account_plans', function (Blueprint $table) {
                $table->dropColumn('linea_estado');
            });
        }
    }

    public function down(): void
    {
        Schema::table('account_plans', function (Blueprint $table) {
            $table->string('linea_estado', 60)->nullable();
        });
    }
};
