<?php

namespace Tests\Feature;

use App\Models\AccountingMap;
use App\Models\AccountPlan;
use App\Models\Empresa;
use App\Models\InventoryItem;
use App\Models\ProductionMaterial;
use App\Models\ProductionOrder;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\AccountingService;
use App\Services\KardexService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Compra y producción entrando por el kardex.
 *
 * Hasta ahora las dos movían el stock con increment/decrement: el costo
 * promedio no se formaba nunca y la valoración quedaba sin uso. Estas pruebas
 * fijan lo contrario: que una compra forme el promedio, que la producción
 * consuma a ese promedio y devuelva el producto terminado a su costo real, y
 * que el asiento contable cuadre con lo que dice el kardex.
 *
 * Corre contra Postgres, no contra el SQLite de phpunit.xml:
 *   DB_CONNECTION=pgsql DB_DATABASE=erp_mashaec_test DB_USERNAME=erp_user \
 *   DB_PASSWORD=… php artisan test --filter=CompraProduccionKardexTest
 */
class CompraProduccionKardexTest extends TestCase
{
    use DatabaseTransactions;

    private Empresa $empresa;
    private InventoryItem $materia;
    private InventoryItem $terminado;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::create([
            'name' => 'Rivet kardex', 'slug' => 'rivet-kardex-cp-' . uniqid(),
            'email' => 'kardex@rivet.ec', 'activo' => true,
        ]);

        $this->materia   = $this->item('MP-014', 'Ácido cítrico', 'materia_prima');
        $this->terminado = $this->item('PT-002', 'Licor Pinteño 500 ml', 'producto_terminado');

        $this->planContableMinimo();
    }

    public function test_la_compra_forma_el_costo_promedio(): void
    {
        $this->confirmarCompra(cantidad: 50, precio: 4.00);

        $this->materia->refresh();

        $this->assertEquals(50, (float) $this->materia->stock_actual);
        $this->assertEquals(4.0, (float) $this->materia->costo_promedio);
        $this->assertEquals(200.0, (float) $this->materia->saldo_valorado);

        $movimiento = $this->materia->movimientos()->withoutGlobalScopes()->latest('id')->first();
        $this->assertSame('compra', $movimiento->motivo);
        $this->assertEquals(50, (float) $movimiento->saldo_cantidad);
        $this->assertNotNull($movimiento->journal_entry_id, 'el movimiento debe apuntar a su asiento');
    }

    public function test_dos_compras_a_distinto_precio_dan_promedio_ponderado(): void
    {
        $this->confirmarCompra(cantidad: 50, precio: 4.00);
        $this->confirmarCompra(cantidad: 40, precio: 4.60);

        $this->materia->refresh();

        // (200 + 184) / 90
        $this->assertEquals(90, (float) $this->materia->stock_actual);
        $this->assertEquals(4.2667, round((float) $this->materia->costo_promedio, 4));
        $this->assertEquals(384.0, (float) $this->materia->saldo_valorado);
    }

    public function test_la_produccion_consume_al_promedio_y_devuelve_el_costo_real(): void
    {
        $this->confirmarCompra(cantidad: 50, precio: 4.00);
        $this->confirmarCompra(cantidad: 40, precio: 4.60);

        $orden = ProductionOrder::create([
            'empresa_id'         => $this->empresa->id,
            'inventory_item_id'  => $this->terminado->id,
            'cantidad_producida' => 10,
            'costo_total'        => 0,
            'fecha'              => now()->toDateString(),
            'estado'             => 'en_proceso',
        ]);

        ProductionMaterial::create([
            'production_order_id' => $orden->id,
            'inventory_item_id'   => $this->materia->id,
            'cantidad_consumida'  => 45,
            'costo_unitario'      => 0,
            'costo_total'         => 0,
        ]);

        $orden->update(['estado' => 'completado']);

        $this->materia->refresh();
        $this->terminado->refresh();
        $orden->refresh();

        // 45 al promedio de 4,2667 = 192,00
        $this->assertEquals(45, (float) $this->materia->stock_actual);
        $this->assertEquals(4.2667, round((float) $this->materia->costo_promedio, 4), 'el consumo no mueve el promedio');
        $this->assertEquals(192.0, round((float) $orden->costo_total, 2));

        // El producto terminado entra a 192 / 10
        $this->assertEquals(10, (float) $this->terminado->stock_actual);
        $this->assertEquals(19.2, round((float) $this->terminado->costo_promedio, 2));
        $this->assertEquals(0, (float) $this->terminado->purchase_price, 'la producción ya no pisa el precio de compra');

        $asiento = $orden->journalEntry()->first() ?? \App\Models\JournalEntry::find($orden->journal_entry_id);
        $this->assertEquals(round((float) $asiento->total_debe, 2), round((float) $asiento->total_haber, 2));
        $this->assertEquals(192.0, round((float) $asiento->total_debe, 2), 'el asiento vale lo que dice el kardex');
    }

    public function test_la_devolucion_de_compra_sale_al_costo_con_el_que_entro(): void
    {
        $this->confirmarCompra(cantidad: 50, precio: 4.00);
        $this->confirmarCompra(cantidad: 50, precio: 6.00);

        // promedio = 5,00
        $this->materia->refresh();
        $this->assertEquals(5.0, (float) $this->materia->costo_promedio);

        app(KardexService::class)->registrar([
            'item'           => $this->materia,
            'motivo'         => 'devolucion_compra',
            'cantidad'       => 50,
            'costo_unitario' => 6.00, // el costo de la compra que se anula
            'fecha'          => now()->toDateString(),
        ]);

        $this->materia->refresh();

        // Quedan las 50 primeras a 4,00: el promedio vuelve a 4, no se queda en 5.
        $this->assertEquals(50, (float) $this->materia->stock_actual);
        $this->assertEquals(200.0, (float) $this->materia->saldo_valorado);
        $this->assertEquals(4.0, (float) $this->materia->costo_promedio);
    }

    // ---------------------------------------------------------------- helpers

    private function item(string $codigo, string $nombre, string $tipo): InventoryItem
    {
        return InventoryItem::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id,
            'codigo' => $codigo, 'nombre' => $nombre, 'type' => $tipo,
            'conversion_factor' => 1,
            'stock_actual' => 0, 'costo_promedio' => 0, 'saldo_valorado' => 0,
            'purchase_price' => 0, 'activo' => true,
        ]);
    }

    private function confirmarCompra(float $cantidad, float $precio): Purchase
    {
        $compra = Purchase::create([
            'empresa_id' => $this->empresa->id,
            'number'     => 'COM-' . uniqid(),
            'date'       => now()->toDateString(),
            'subtotal'   => $cantidad * $precio,
            'iva'        => 0,
            'total'      => $cantidad * $precio,
            'status'     => 'borrador',
            'forma_pago' => 'efectivo',
        ]);

        PurchaseItem::create([
            'purchase_id'       => $compra->id,
            'inventory_item_id' => $this->materia->id,
            'quantity'          => $cantidad,
            'unit_price'        => $precio,
            'aplica_iva'        => false,
            'subtotal'          => $cantidad * $precio,
            'iva_monto'         => 0,
            'total_item'        => $cantidad * $precio,
        ]);

        app(AccountingService::class)->generarAsientoCompra($compra->fresh('items'));

        return $compra;
    }

    /** Lo mínimo del plan de cuentas para que el asiento se pueda armar. */
    private function planContableMinimo(): void
    {
        $cuentas = [
            '1.1.01.03' => ['Bancos', 'activo', 'deudora'],
            '1.1.03.01' => ['Inventario materia prima', 'activo', 'deudora'],
            '1.1.03.03' => ['Inventario producto terminado', 'activo', 'deudora'],
            '1.1.02.05' => ['IVA crédito tributario', 'activo', 'deudora'],
        ];

        $creadas = [];
        foreach ($cuentas as $code => [$name, $type, $nature]) {
            $creadas[$code] = AccountPlan::withoutGlobalScopes()->create([
                'empresa_id' => $this->empresa->id,
                'code' => $code, 'name' => $name, 'type' => $type, 'nature' => $nature,
                'level' => 4, 'accepts_movements' => true, 'is_active' => true,
            ]);
        }

        $mapeos = [
            ['materia_prima',      'compra_contado',     '1.1.03.01'],
            ['producto_terminado', 'compra_contado',     '1.1.03.03'],
            ['global',             'compra_contado',     '1.1.01.03'],
            ['materia_prima',      'iva_compras',        '1.1.02.05'],
            ['global',             'iva_compras',        '1.1.02.05'],
            ['producto_terminado', 'entrada_produccion', '1.1.03.03'],
            ['materia_prima',      'salida_produccion',  '1.1.03.01'],
        ];

        foreach ($mapeos as [$tipoItem, $tipoMovimiento, $code]) {
            AccountingMap::withoutGlobalScopes()->create([
                'empresa_id' => $this->empresa->id,
                'tipo_item' => $tipoItem,
                'tipo_movimiento' => $tipoMovimiento,
                'account_plan_id' => $creadas[$code]->id,
            ]);
        }
    }
}
