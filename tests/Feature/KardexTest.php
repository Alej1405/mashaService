<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\InventoryItem;
use App\Models\PeriodoContable;
use App\Services\KardexService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Kardex valorado con promedio ponderado.
 *
 * El caso central es el que se da todos los meses: el mismo insumo entra a dos
 * precios distintos y hay que saber con cuál sale cada lote de producción.
 *
 * Corre contra Postgres, no contra el SQLite de phpunit.xml (ver SitioApiTest):
 *   DB_CONNECTION=pgsql DB_DATABASE=erp_mashaec_test DB_USERNAME=erp_user \
 *   DB_PASSWORD=… php artisan test --filter=KardexTest
 */
class KardexTest extends TestCase
{
    use DatabaseTransactions;

    private Empresa $empresa;
    private InventoryItem $item;
    private KardexService $kardex;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::create([
            'name' => 'Rivet pruebas', 'slug' => 'rivet-kardex-' . uniqid(),
            'email' => 'pruebas@rivet.ec', 'activo' => true,
        ]);

        $this->item = InventoryItem::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id,
            'codigo' => 'MP-014', 'nombre' => 'Ácido cítrico', 'type' => 'materia_prima',
            'stock_actual' => 0, 'costo_promedio' => 0, 'saldo_valorado' => 0, 'activo' => true,
        ]);

        $this->kardex = new KardexService();
    }

    public function test_el_stock_sin_costo_toma_el_precio_de_la_primera_compra(): void
    {
        // El caso del despliegue: el ítem trae stock del histórico y ningún valor.
        $this->item->forceFill(['stock_actual' => 1, 'costo_promedio' => 0, 'saldo_valorado' => 0])->save();

        $this->entrada(10, 10.00, '2026-09-22');

        $this->item->refresh();

        $this->assertEquals(11, (float) $this->item->stock_actual);
        $this->assertEquals(10.0, (float) $this->item->costo_promedio, 'no se promedia contra cero');
        $this->assertEquals(110.0, (float) $this->item->saldo_valorado);
    }

    private function entrada(float $cantidad, float $costo, string $fecha): void
    {
        $this->kardex->registrar([
            'item' => $this->item->fresh(), 'motivo' => 'compra',
            'cantidad' => $cantidad, 'costo_unitario' => $costo, 'fecha' => $fecha,
        ]);
    }

    private function salida(float $cantidad, string $fecha)
    {
        return $this->kardex->registrar([
            'item' => $this->item->fresh(), 'motivo' => 'consumo_produccion',
            'cantidad' => $cantidad, 'fecha' => $fecha,
        ]);
    }

    public function test_el_promedio_se_recalcula_al_comprar_a_otro_precio(): void
    {
        $this->entrada(50, 4.00, '2026-09-05');
        $this->assertEquals(4.0000, round($this->item->fresh()->costo_promedio, 4));

        $this->salida(45, '2026-09-12');
        $item = $this->item->fresh();
        $this->assertEquals(5.0000, round($item->stock_actual, 4), 'la salida no cambia el promedio');
        $this->assertEquals(4.0000, round($item->costo_promedio, 4));

        // Segunda compra más cara: aquí es donde el costo del lote cambia.
        $this->entrada(40, 4.60, '2026-09-18');
        $item = $this->item->fresh();
        $this->assertEquals(45.0000, round($item->stock_actual, 4));
        // (5 × 4,00 + 40 × 4,60) / 45 = 4,5333
        $this->assertEquals(4.5333, round($item->costo_promedio, 4));
    }

    public function test_la_salida_se_valora_al_promedio_vigente_no_al_ultimo_precio(): void
    {
        $this->entrada(50, 4.00, '2026-09-05');
        $this->entrada(50, 6.00, '2026-09-18');

        $movimiento = $this->salida(10, '2026-09-20');

        // El último precio fue 6,00; el promedio es 5,00. Sale a 5,00.
        $this->assertEquals(5.0000, round($movimiento->unit_price, 4));
        $this->assertEquals(50.0000, round($movimiento->total, 4));
    }

    public function test_cada_movimiento_guarda_el_saldo_que_dejo(): void
    {
        $this->entrada(50, 4.00, '2026-09-05');
        $movimiento = $this->salida(20, '2026-09-12');

        $this->assertEquals(30.0000, round($movimiento->saldo_cantidad, 4));
        $this->assertEquals(4.0000,  round($movimiento->costo_promedio, 4));
        $this->assertEquals(120.0000, round($movimiento->saldo_valor, 4));
    }

    public function test_una_entrada_sin_costo_no_se_registra(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->entrada(10, 0, '2026-09-05');
    }

    public function test_no_se_puede_sacar_mas_de_lo_que_hay(): void
    {
        $this->entrada(10, 4.00, '2026-09-05');

        $this->expectException(\RuntimeException::class);
        $this->salida(11, '2026-09-06');
    }

    public function test_una_baja_sin_acta_no_se_registra(): void
    {
        $this->entrada(10, 4.00, '2026-09-05');

        $this->expectException(\InvalidArgumentException::class);
        $this->kardex->registrar([
            'item' => $this->item->fresh(), 'motivo' => 'baja',
            'cantidad' => 2, 'fecha' => '2026-09-10',
        ]);
    }

    public function test_un_periodo_cerrado_no_admite_movimientos(): void
    {
        PeriodoContable::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'anio' => 2026, 'mes' => 8, 'cerrado_en' => now(),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->entrada(10, 4.00, '2026-08-15');
    }
}
