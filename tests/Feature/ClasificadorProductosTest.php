<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\InventoryItem;
use App\Models\TipoGasto;
use App\Services\ClasificadorProductos;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * El clasificador propone qué es una línea de factura sin que quien registra
 * tenga que saberlo: servicio o bien, y a qué tipo de gasto o ítem va.
 *
 * Corre contra Postgres (los tipos de gasto los siembra una migración):
 *   DB_CONNECTION=pgsql DB_DATABASE=erp_mashaec_test … php artisan test --filter=ClasificadorProductosTest
 */
class ClasificadorProductosTest extends TestCase
{
    use DatabaseTransactions;

    private Empresa $empresa;
    private ClasificadorProductos $clasificador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->empresa = Empresa::create([
            'name' => 'Rivet clasificador', 'slug' => 'rivet-clas-' . uniqid(), 'email' => 'c@rivet.ec',
            'activo' => true, 'plan' => 'pro', 'numero_identificacion' => '1793229485001',
        ]);
        $this->clasificador = app(ClasificadorProductos::class);
    }

    private function gasto(string $codigo): string
    {
        return 'gasto:' . TipoGasto::where('codigo', $codigo)->value('id');
    }

    public function test_un_servicio_de_control_de_plagas_va_a_mantenimiento(): void
    {
        $r = $this->clasificador->proponer($this->empresa->id,
            'POR CONCEPTO DE SERVICIOS TECNICOS Y LOGISTICOS PARA EFECTUAR EL CONTROL DE PLAGAS EN RIVET ECUADOR');

        $this->assertSame($this->gasto('mantenimiento-y-reparaciones'), $r['valor']);
        $this->assertTrue($r['servicio']);
        $this->assertStringContainsString('control de plagas', $r['motivo']);
    }

    public function test_la_gasolina_va_a_combustible_aunque_la_factura_solo_diga_extra(): void
    {
        $this->assertSame($this->gasto('combustible'), $this->clasificador->proponer($this->empresa->id, 'EXTRA')['valor']);
    }

    public function test_los_honorarios_distinguen_persona_natural_de_sociedad(): void
    {
        $natural  = $this->clasificador->proponer($this->empresa->id, 'Asesoria contable', '1712345678001');
        $sociedad = $this->clasificador->proponer($this->empresa->id, 'Asesoria contable', '1790012345001');

        $this->assertSame($this->gasto('honorarios-profesionales'), $natural['valor']);
        $this->assertSame($this->gasto('servicios-profesionales-de-sociedades'), $sociedad['valor']);
    }

    public function test_un_bien_que_existe_va_a_su_item_y_uno_nuevo_se_propone_crear(): void
    {
        $item = InventoryItem::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'nombre' => 'Sorbato de Potasio', 'type' => 'materia_prima', 'activo' => true,
        ]);

        $this->assertSame("item:{$item->id}", $this->clasificador->proponer($this->empresa->id, 'SORBATO DE POTASIO 1 KG')['valor']);
        $this->assertSame('nuevo:insumo', $this->clasificador->proponer($this->empresa->id, 'BOTELLA PET 500CC PASO 28MM')['valor']);
        $this->assertSame('nuevo:materia_prima', $this->clasificador->proponer($this->empresa->id, 'Esencia de maracuya 1 lt')['valor']);
    }
}
