<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\InventoryItem;
use App\Services\KardexService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Con qué costo sale un producto cuando se vende.
 *
 * Antes salía con inventory_items.purchase_price, el último precio de compra.
 * Eso recosteaba hacia atrás: comprar más caro hoy encarecía la venta de ayer.
 * Ahora sale con el promedio ponderado del kardex.
 *
 *   DB_CONNECTION=pgsql DB_DATABASE=erp_mashaec_test DB_USERNAME=erp_user \
 *   DB_PASSWORD=… php artisan test --filter=CosteoVentaTest
 */
class CosteoVentaTest extends TestCase
{
    use DatabaseTransactions;

    private function itemConHistoria(): InventoryItem
    {
        $empresa = Empresa::create([
            'name' => 'Rivet costeo', 'slug' => 'rivet-costeo-' . uniqid(),
            'email' => 'costeo@rivet.ec', 'activo' => true,
        ]);

        $item = InventoryItem::withoutGlobalScopes()->create([
            'empresa_id' => $empresa->id, 'codigo' => 'MP-COST', 'nombre' => 'Ácido cítrico',
            'type' => 'materia_prima', 'stock_actual' => 0, 'costo_promedio' => 0,
            'saldo_valorado' => 0, 'purchase_price' => 9.99, 'activo' => true,
        ]);

        $kardex = new KardexService();
        $kardex->registrar(['item' => $item->fresh(), 'motivo' => 'compra', 'cantidad' => 50, 'costo_unitario' => 4.00, 'fecha' => '2026-09-05']);
        $kardex->registrar(['item' => $item->fresh(), 'motivo' => 'compra', 'cantidad' => 50, 'costo_unitario' => 4.60, 'fecha' => '2026-09-18']);

        return $item->fresh();
    }

    public function test_el_promedio_manda_sobre_el_precio_de_la_ficha(): void
    {
        $item = $this->itemConHistoria();

        // La ficha dice 9,99. El kardex dice 4,30. Gana el kardex.
        $this->assertEquals(9.99, round($item->purchase_price, 2));
        $this->assertEquals(4.30, round($item->costo_promedio, 2));

        $costoAplicado = (float) ($item->costo_promedio > 0
            ? $item->costo_promedio
            : ($item->purchase_price ?? 0));

        $this->assertEquals(4.30, round($costoAplicado, 2));
    }

    public function test_comprar_mas_caro_hoy_no_recostea_lo_de_ayer(): void
    {
        $item   = $this->itemConHistoria();
        $kardex = new KardexService();

        $salidaAntes = $kardex->registrar([
            'item' => $item->fresh(), 'motivo' => 'venta', 'cantidad' => 10, 'fecha' => '2026-09-19',
        ]);

        // Entra una compra mucho más cara después de esa salida.
        $kardex->registrar([
            'item' => $item->fresh(), 'motivo' => 'compra', 'cantidad' => 100,
            'costo_unitario' => 12.00, 'fecha' => '2026-09-21',
        ]);

        // La salida ya registrada conserva su costo: la historia no se reescribe.
        $this->assertEquals(4.30, round($salidaAntes->fresh()->unit_price, 2));
    }

    public function test_el_valor_en_bodega_baja_junto_con_el_stock(): void
    {
        $item   = $this->itemConHistoria();
        $kardex = new KardexService();

        $valorAntes = (float) $item->saldo_valorado;
        $kardex->registrar(['item' => $item->fresh(), 'motivo' => 'venta', 'cantidad' => 20, 'fecha' => '2026-09-20']);

        $despues = $item->fresh();
        $this->assertEquals(80.0, round($despues->stock_actual, 2));
        // 20 unidades × 4,30 = 86,00 menos
        $this->assertEquals(round($valorAntes - 86.00, 2), round($despues->saldo_valorado, 2));
    }
}
