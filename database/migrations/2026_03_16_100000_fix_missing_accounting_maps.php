<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\AccountPlan;
use App\Models\AccountingMap;
use App\Models\Empresa;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Corrige los hallazgos críticos H1, H2 y H3 de auditoría:
     *  - Amplía el ENUM tipo_movimiento con entrada_produccion y salida_produccion
     *  - Agrega mapeos faltantes al catálogo base (empresa_id=null)
     *  - Para cada empresa existente: clona las cuentas de módulo que le falten
     *    y luego propaga los nuevos mapeos
     */
    public function up(): void
    {
        // ── 1. Ampliar ENUM tipo_movimiento ──────────────────────────────────
        DB::statement("ALTER TABLE accounting_maps MODIFY COLUMN tipo_movimiento ENUM(
            'compra_contado',
            'compra_credito_local',
            'compra_credito_exterior',
            'venta_contado',
            'venta_credito',
            'consumo_produccion',
            'costo_venta',
            'iva_compras',
            'iva_ventas',
            'depreciacion',
            'ajuste_inventario',
            'ajuste_sobrante',
            'entrada_produccion',
            'salida_produccion'
        ) NOT NULL");

        $nuevosMapas = [
            ['producto_terminado', 'compra_contado',          '1.1.03.04'],
            ['producto_terminado', 'compra_credito_local',    '1.1.03.04'],
            ['producto_terminado', 'compra_credito_exterior', '1.1.03.04'],
            ['producto_terminado', 'iva_compras',             '1.1.05.01'],
            ['producto_terminado', 'ajuste_inventario',       '6.3.03'],
            ['producto_terminado', 'ajuste_sobrante',         '4.3.02'],
            ['producto_terminado', 'entrada_produccion',      '1.1.03.04'],
            ['producto_terminado', 'salida_produccion',       '1.1.03.04'],
            ['materia_prima',      'salida_produccion',       '1.1.03.02'],
            ['materia_prima',      'entrada_produccion',      '1.1.03.02'],
            ['insumo',             'salida_produccion',       '1.1.03.01'],
            ['insumo',             'entrada_produccion',      '1.1.03.01'],
            ['global',             'iva_compras',             '1.1.05.01'],
            ['global',             'iva_ventas',              '2.1.04.01'],
        ];

        // ── 2. Insertar mapas base (empresa_id = null) ──────────────────────
        foreach ($nuevosMapas as [$tipoItem, $tipoMovimiento, $codigoCuenta]) {
            $cuentaBase = AccountPlan::withoutGlobalScopes()
                ->whereNull('empresa_id')
                ->where('code', $codigoCuenta)
                ->first();

            if (! $cuentaBase) continue;

            AccountingMap::withoutGlobalScopes()->updateOrCreate(
                [
                    'empresa_id'      => null,
                    'tipo_item'       => $tipoItem,
                    'tipo_movimiento' => $tipoMovimiento,
                ],
                ['account_plan_id' => $cuentaBase->id]
            );
        }

        // ── 3. Para cada empresa: clonar cuentas faltantes y agregar mapeos ─
        $empresas = Empresa::withoutGlobalScopes()->get();

        // Módulos que pueden estar habilitados y sus cuentas de inventario requeridas
        $modulosCuentas = [
            'tipo_operacion_productos'   => ['modulo' => 'productos',   'codigos' => ['1.1.03.04', '4.1.01', '4.1.03', '4.1.04', '5.1', '5.1.04']],
            'tipo_operacion_manufactura' => ['modulo' => 'manufactura',  'codigos' => ['1.1.03.01', '1.1.03.02', '1.1.03.03', '5.1.01', '5.1.02', '5.1.03', '5.1.05']],
            'tipo_operacion_servicios'   => ['modulo' => 'servicios',    'codigos' => ['4.1.02']],
            'tiene_logistica'            => ['modulo' => 'logistica',    'codigos' => []],
        ];

        foreach ($empresas as $empresa) {
            // Clonar cuentas de módulos habilitados que no existan aún
            foreach ($modulosCuentas as $campo => $info) {
                if (! $empresa->$campo) continue;

                $cuentasBase = AccountPlan::withoutGlobalScopes()
                    ->whereNull('empresa_id')
                    ->where('modulo', $info['modulo'])
                    ->get();

                foreach ($cuentasBase as $cuentaBase) {
                    AccountPlan::withoutGlobalScopes()->updateOrCreate(
                        ['empresa_id' => $empresa->id, 'code' => $cuentaBase->code],
                        [
                            'name'              => $cuentaBase->name,
                            'type'              => $cuentaBase->type,
                            'nature'            => $cuentaBase->nature,
                            'parent_code'       => $cuentaBase->parent_code,
                            'level'             => $cuentaBase->level,
                            'accepts_movements' => $cuentaBase->accepts_movements,
                            'modulo'            => $cuentaBase->modulo,
                            'is_active'         => true,
                        ]
                    );
                }
            }

            // Agregar los mapeos nuevos para esta empresa
            foreach ($nuevosMapas as [$tipoItem, $tipoMovimiento, $codigoCuenta]) {
                $yaExiste = AccountingMap::withoutGlobalScopes()
                    ->where('empresa_id', $empresa->id)
                    ->where('tipo_item', $tipoItem)
                    ->where('tipo_movimiento', $tipoMovimiento)
                    ->exists();

                if ($yaExiste) continue;

                $cuentaEmpresa = AccountPlan::withoutGlobalScopes()
                    ->where('empresa_id', $empresa->id)
                    ->where('code', $codigoCuenta)
                    ->first();

                if (! $cuentaEmpresa) continue;

                AccountingMap::withoutGlobalScopes()->create([
                    'empresa_id'      => $empresa->id,
                    'tipo_item'       => $tipoItem,
                    'tipo_movimiento' => $tipoMovimiento,
                    'account_plan_id' => $cuentaEmpresa->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        $nuevosMapas = [
            ['producto_terminado', 'compra_contado'],
            ['producto_terminado', 'compra_credito_local'],
            ['producto_terminado', 'compra_credito_exterior'],
            ['producto_terminado', 'iva_compras'],
            ['producto_terminado', 'ajuste_inventario'],
            ['producto_terminado', 'ajuste_sobrante'],
            ['producto_terminado', 'entrada_produccion'],
            ['producto_terminado', 'salida_produccion'],
            ['materia_prima',      'salida_produccion'],
            ['materia_prima',      'entrada_produccion'],
            ['insumo',             'salida_produccion'],
            ['insumo',             'entrada_produccion'],
            ['global',             'iva_compras'],
            ['global',             'iva_ventas'],
        ];

        foreach ($nuevosMapas as [$tipoItem, $tipoMovimiento]) {
            AccountingMap::withoutGlobalScopes()
                ->where('tipo_item', $tipoItem)
                ->where('tipo_movimiento', $tipoMovimiento)
                ->delete();
        }

        DB::statement("ALTER TABLE accounting_maps MODIFY COLUMN tipo_movimiento ENUM(
            'compra_contado',
            'compra_credito_local',
            'compra_credito_exterior',
            'venta_contado',
            'venta_credito',
            'consumo_produccion',
            'costo_venta',
            'iva_compras',
            'iva_ventas',
            'depreciacion',
            'ajuste_inventario',
            'ajuste_sobrante'
        ) NOT NULL");
    }
};
