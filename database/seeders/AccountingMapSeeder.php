<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountingMap;
use App\Models\AccountPlan;

class AccountingMapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $map = [
            // Insumos
            ['insumo', 'compra_contado', '1.1.03.01'],
            ['insumo', 'compra_credito_local', '1.1.03.01'],
            ['insumo', 'compra_credito_exterior', '1.1.03.01'],
            ['insumo', 'consumo_produccion', '5.1.02'],
            ['insumo', 'iva_compras', '1.1.05.01'],
            ['insumo', 'ajuste_inventario', '6.3.03'],
            ['insumo', 'ajuste_sobrante', '4.3.02'],

            // Materias Primas
            ['materia_prima', 'compra_contado', '1.1.03.02'],
            ['materia_prima', 'compra_credito_local', '1.1.03.02'],
            ['materia_prima', 'compra_credito_exterior', '1.1.03.02'],
            ['materia_prima', 'consumo_produccion', '5.1.01'],
            ['materia_prima', 'iva_compras', '1.1.05.01'],
            ['materia_prima', 'ajuste_inventario', '6.3.03'],
            ['materia_prima', 'ajuste_sobrante', '4.3.02'],

            // Productos Terminados
            ['producto_terminado', 'venta_contado', '4.1.01'],
            ['producto_terminado', 'venta_credito', '4.1.01'],
            ['producto_terminado', 'costo_venta', '5.1.04'],
            ['producto_terminado', 'iva_ventas', '2.1.04.01'],

            // Productos en Proceso
            ['producto_en_proceso', 'consumo_produccion', '1.1.03.03'],

            // Activos Fijos - Maquinaria
            ['activo_fijo_maquinaria', 'compra_contado', '1.2.01.04'],
            ['activo_fijo_maquinaria', 'compra_credito_local', '1.2.01.04'],
            ['activo_fijo_maquinaria', 'depreciacion', '1.2.01.07'],

            // Activos Fijos - Cómputo
            ['activo_fijo_computo', 'compra_contado', '1.2.01.05'],
            ['activo_fijo_computo', 'compra_credito_local', '1.2.01.05'],
            ['activo_fijo_computo', 'depreciacion', '1.2.01.07'],

            // Activos Fijos - Vehículo
            ['activo_fijo_vehiculo', 'compra_contado', '1.2.01.06'],
            ['activo_fijo_vehiculo', 'compra_credito_local', '1.2.01.06'],
            ['activo_fijo_vehiculo', 'depreciacion', '1.2.01.07'],

            // Activos Fijos - Muebles
            ['activo_fijo_muebles', 'compra_contado', '1.2.01.03'],
            ['activo_fijo_muebles', 'compra_credito_local', '1.2.01.03'],
            ['activo_fijo_muebles', 'depreciacion', '1.2.01.07'],

            // Servicios
            ['servicio', 'compra_contado', '6.1.20'],
            ['servicio', 'compra_credito_local', '6.1.20'],
            ['servicio', 'iva_compras', '1.1.05.01'],
            ['servicio', 'venta_contado', '4.1.02'],
            ['servicio', 'venta_credito', '4.1.02'],
            ['servicio', 'iva_ventas', '2.1.04.01'],

            // Productos Terminados (compras y producción)
            ['producto_terminado', 'compra_contado', '1.1.03.04'],
            ['producto_terminado', 'compra_credito_local', '1.1.03.04'],
            ['producto_terminado', 'compra_credito_exterior', '1.1.03.04'],
            ['producto_terminado', 'iva_compras', '1.1.05.01'],
            ['producto_terminado', 'ajuste_inventario', '6.3.03'],
            ['producto_terminado', 'ajuste_sobrante', '4.3.02'],
            ['producto_terminado', 'entrada_produccion', '1.1.03.04'],
            ['producto_terminado', 'salida_produccion', '1.1.03.04'],

            // Producción — movimientos de materiales y producto terminado
            ['materia_prima', 'salida_produccion', '1.1.03.02'],
            ['materia_prima', 'entrada_produccion', '1.1.03.02'],
            ['insumo', 'salida_produccion', '1.1.03.01'],
            ['insumo', 'entrada_produccion', '1.1.03.01'],

            // Contrapartidas Globales
            ['global', 'compra_contado', '1.1.01.03'], // Bancos
            ['global', 'compra_credito_local', '2.1.01.01'], // Proveedores locales
            ['global', 'compra_credito_exterior', '2.1.01.03'], // Proveedores exterior
            ['global', 'venta_contado', '1.1.01.03'], // Bancos
            ['global', 'venta_credito', '1.1.02.01'], // CxC clientes
            ['global', 'iva_compras', '1.1.05.01'], // Fallback IVA compras
            ['global', 'iva_ventas', '2.1.04.01'],  // Fallback IVA ventas
        ];

        foreach ($map as $m) {
            $cuenta = AccountPlan::whereNull('empresa_id')->where('code', $m[2])->first();

            if ($cuenta) {
                AccountingMap::updateOrCreate(
                    [
                        'empresa_id' => null,
                        'tipo_item' => $m[0],
                        'tipo_movimiento' => $m[1],
                    ],
                    [
                        'account_plan_id' => $cuenta->id,
                    ]
                );
            }
        }
    }
}
