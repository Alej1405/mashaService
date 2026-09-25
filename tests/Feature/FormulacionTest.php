<?php

namespace Tests\Feature;

use App\Models\AccountPlan;
use App\Models\Empresa;
use App\Models\InventoryItem;
use App\Models\MeasurementUnit;
use App\Models\ProductDesign;
use App\Models\ProductFormulaLine;
use App\Models\ProductPresentation;
use App\Services\ServicioFormulacion;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Formulación: unidades, merma y materia prima procesada.
 *
 * Lo que se comprueba no es que el código corra, sino que el número que sale
 * sea el mismo por los dos caminos: lo que dice la receta y lo que dice el
 * kardex de cada insumo. El cálculo lo hace el microservicio; estas pruebas
 * verifican que el ERP le manda bien el catálogo y entiende la respuesta.
 *
 *   DB_CONNECTION=pgsql DB_DATABASE=erp_mashaec_test … php artisan test --filter=FormulacionTest
 */
class FormulacionTest extends TestCase
{
    use DatabaseTransactions;

    private Empresa $empresa;
    private array $unidades = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (! app(ServicioFormulacion::class)->configurado()) {
            $this->markTestSkipped('el servicio de formulación no está configurado en este entorno');
        }

        $this->empresa = Empresa::create([
            'name' => 'Formulación', 'slug' => 'form-' . uniqid(),
            'email' => 'f@rivet.ec', 'activo' => true, 'plan' => 'enterprise',
        ]);

        foreach ([['Mililitro', 'ml', 'volumen', 1], ['Litro', 'l', 'volumen', 1000],
                  ['Gramo', 'g', 'masa', 1], ['Kilo', 'kg', 'masa', 1000],
                  ['Unidad', 'u', 'conteo', 1]] as [$nombre, $abr, $tipo, $factor]) {
            $this->unidades[$abr] = MeasurementUnit::withoutGlobalScopes()->create([
                'empresa_id' => $this->empresa->id, 'nombre' => $nombre,
                'abreviatura' => $abr, 'tipo' => $tipo, 'factor' => $factor, 'activo' => true,
            ])->id;
        }
    }

    private function item(string $nombre, string $tipo, string $unidad, float $costo): InventoryItem
    {
        return InventoryItem::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'nombre' => $nombre,
            'codigo' => strtoupper(substr(md5($nombre . uniqid()), 0, 8)),
            'type' => $tipo, 'measurement_unit_id' => $this->unidades[$unidad],
            'purchase_price' => $costo, 'costo_promedio' => $costo,
            'stock_actual' => 0, 'saldo_valorado' => 0, 'activo' => true,
        ]);
    }

    private function presentacion(string $nombre, float $rinde, string $unidad): ProductPresentation
    {
        $diseno = ProductDesign::firstOrCreate(
            ['empresa_id' => $this->empresa->id, 'nombre' => 'Diseño de prueba'], ['activo' => true],
        );

        return ProductPresentation::create([
            'product_design_id' => $diseno->id, 'nombre' => $nombre,
            'measurement_unit_id' => $this->unidades[$unidad], 'activa' => true,
            'cantidad_minima_produccion' => $rinde,
        ]);
    }

    private function linea(ProductPresentation $p, InventoryItem $i, float $cantidad, string $unidad, float $merma = 0): void
    {
        ProductFormulaLine::create([
            'presentation_id' => $p->id, 'inventory_item_id' => $i->id,
            'cantidad' => $cantidad, 'measurement_unit_id' => $this->unidades[$unidad],
            'merma_porcentaje' => $merma,
        ]);
    }

    public function test_la_receta_convierte_a_la_unidad_del_kardex(): void
    {
        // El azúcar se compra y se lleva en kilos; la receta pide gramos.
        $azucar = $this->item('Azúcar', 'materia_prima', 'kg', 2.00);
        $p = $this->presentacion('Mezcla', 1, 'u');
        $this->linea($p, $azucar, 1500, 'g');

        $r = app(ServicioFormulacion::class)->costear($p);

        $this->assertTrue($r['ok'], $r['error'] ?? '');
        // 1500 g = 1,5 kg × $2 = $3, no 1500 × 2 = $3000.
        $this->assertEquals(3.0, round($r['costo_unitario'], 4));
        $this->assertEquals(1.5, $r['lineas'][0]['cantidad_normalizada']);
        $this->assertEquals('kg', $r['lineas'][0]['unidad_normalizada']);
    }

    public function test_la_merma_encarece_la_linea(): void
    {
        $mortino = $this->item('Mortiño', 'materia_prima', 'kg', 10.00);
        $p = $this->presentacion('Con merma', 1, 'u');
        $this->linea($p, $mortino, 1, 'kg', 20);

        $r = app(ServicioFormulacion::class)->costear($p);

        // Para que queden 1 kg útiles con 20 % de pérdida hay que partir de 1,25.
        $this->assertEquals(1.25, round($r['lineas'][0]['cantidad_con_merma'], 4));
        $this->assertEquals(12.5, round($r['costo_unitario'], 4));
    }

    public function test_la_materia_prima_procesada_trae_el_costo_de_su_receta(): void
    {
        $alcohol = $this->item('Alcohol', 'materia_prima', 'l', 4.00);   // $4 el litro

        // El macerado se produce: 10 litros con 8 litros de alcohol.
        $recetaMacerado = $this->presentacion('Macerado 10 L', 10000, 'ml');
        $this->linea($recetaMacerado, $alcohol, 8, 'l');

        $macerado = $this->item('Macerado', 'materia_prima_procesada', 'ml', 0);
        $macerado->update(['product_presentation_id' => $recetaMacerado->id]);

        // La botella lleva 700 ml de macerado.
        $botella = $this->presentacion('Botella', 1, 'u');
        $this->linea($botella, $macerado, 700, 'ml');

        $r = app(ServicioFormulacion::class)->costear($botella);

        $this->assertTrue($r['ok'], $r['error'] ?? '');
        // 32 dólares el lote / 10000 ml = 0,0032 por ml; 700 ml = 2,24.
        $this->assertEquals(2.24, round($r['costo_unitario'], 4));
        $this->assertSame('receta', $r['lineas'][0]['origen_costo'], 'el costo no puede venir del kardex: se produce');
        $this->assertEquals(32.0, round($r['lineas'][0]['receta']['costo_lote'], 4));
    }

    public function test_no_se_convierte_entre_magnitudes_distintas(): void
    {
        $agua = $this->item('Agua', 'materia_prima', 'l', 1.00);
        $p = $this->presentacion('Imposible', 1, 'u');
        $this->linea($p, $agua, 500, 'g');   // gramos de algo que se lleva en litros

        $r = app(ServicioFormulacion::class)->costear($p);

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('magnitudes distintas', $r['error']);
    }

    public function test_un_insumo_sin_costo_se_avisa_y_no_se_inventa(): void
    {
        $sinCosto = $this->item('Insumo sin costo', 'insumo', 'u', 0);
        $p = $this->presentacion('Con hueco', 1, 'u');
        $this->linea($p, $sinCosto, 1, 'u');

        $r = app(ServicioFormulacion::class)->costear($p);

        $this->assertTrue($r['ok']);
        $this->assertEquals(0.0, $r['costo_unitario']);
        $this->assertNotEmpty($r['hallazgos'], 'un costo en cero tiene que avisarse');
        $this->assertStringContainsString('no tiene costo', $r['hallazgos'][0]['texto']);
    }

    public function test_el_costo_estandar_se_guarda_para_comparar_con_el_real(): void
    {
        $azucar = $this->item('Azúcar guardada', 'materia_prima', 'kg', 2.00);
        $p = $this->presentacion('Para guardar', 1, 'u');
        $this->linea($p, $azucar, 500, 'g');

        app(ServicioFormulacion::class)->costearYGuardar($p);

        $p->refresh();
        $this->assertEquals(1.0, (float) $p->costo_estandar);
        $this->assertNotNull($p->costo_calculado_en);
    }
}
