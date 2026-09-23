<?php

namespace Tests\Feature;

use App\Models\CatalogoSri;
use App\Models\Empresa;
use App\Models\MapaSri;
use App\Services\CatalogoSriService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Los catálogos del SRI.
 *
 * Marco: skill `declaraciones-sri-ec`. Lo que se prueba es lo que esa skill
 * exige: que un código no signifique nada sin su anexo, que la fecha decida el
 * porcentaje, que las tablas se restrinjan entre sí y que el ajuste de una
 * empresa mande sobre la regla del sistema.
 *
 * Corre contra Postgres, no contra el SQLite de phpunit.xml:
 *   DB_CONNECTION=pgsql DB_DATABASE=erp_mashaec_test DB_USERNAME=erp_user \
 *   DB_PASSWORD=… php artisan test --filter=CatalogoSriTest
 */
class CatalogoSriTest extends TestCase
{
    use DatabaseTransactions;

    private CatalogoSriService $sri;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sri = app(CatalogoSriService::class);
    }

    public function test_el_mismo_codigo_significa_cosas_distintas_en_cada_anexo(): void
    {
        $ats      = $this->sri->buscar('ats', '2', '02');
        $rebefics = $this->sri->buscar('rebefics', '1', '02');
        $adi      = $this->sri->buscar('adi', '2', '02');

        $this->assertStringContainsString('CEDULA', $ats->descripcion);
        $this->assertStringContainsString('BOLSAS DE VALORES', $rebefics->descripcion);
        $this->assertStringContainsString('NO RESIDENTE', $adi->descripcion);
    }

    public function test_la_retencion_de_renta_usa_el_porcentaje_del_dia_de_la_compra(): void
    {
        $concepto = 'Servicios con predominio de mano de obra';

        $antes  = $this->sri->conceptoRenta($concepto, '2025-06-15');
        $ahora  = $this->sri->conceptoRenta($concepto, '2026-09-20');

        $this->assertSame('307', $antes['codigo']);
        $this->assertSame('307', $ahora['codigo']);
        // La resolución de marzo de 2026 subió mano de obra del 2 % al 3 %.
        $this->assertSame('2', $antes['porcentaje']);
        $this->assertSame('3', $ahora['porcentaje']);
    }

    public function test_un_par_de_codigos_que_el_sri_rechaza_no_pasa_la_validacion(): void
    {
        // Beneficiario 01 del anexo de dividendos: persona natural residente.
        $this->assertTrue($this->sri->admite('adi', '2', '01', 'C'));
        $this->assertFalse($this->sri->admite('adi', '2', '01', 'E'),
            'una persona natural residente no se identifica con documento del exterior');
    }

    public function test_el_ajuste_de_la_empresa_manda_sobre_la_regla_del_sistema(): void
    {
        $empresa = Empresa::create([
            'name' => 'Prueba SRI', 'slug' => 'prueba-sri-' . uniqid(),
            'email' => 'sri@prueba.ec', 'activo' => true, 'plan' => 'pro',
        ]);

        $this->assertSame('20', $this->sri->codigo('forma_pago', 'transferencia', 'ats', '13'));

        MapaSri::create(['empresa_id' => $empresa->id, 'entidad' => 'forma_pago',
                         'clave' => 'transferencia', 'anexo' => 'ats', 'tabla' => '13', 'codigo' => '08']);

        $this->assertSame('08', $this->sri->codigo('forma_pago', 'transferencia', 'ats', '13', $empresa->id));
        // Sin la empresa, la regla del sistema sigue intacta.
        $this->assertSame('20', $this->sri->codigo('forma_pago', 'transferencia', 'ats', '13'));
    }

    public function test_los_cuatro_catalogos_estan_sembrados(): void
    {
        $inventario = $this->sri->inventario();

        foreach (CatalogoSriService::ANEXOS as $anexo => $nombre) {
            $this->assertArrayHasKey($anexo, $inventario, "falta sembrar {$anexo}");
            $this->assertGreaterThan(50, $inventario[$anexo]['codigos']);
        }

        // Las fórmulas del 104 llegaron con los casilleros.
        $this->assertSame(
            '721+723+725+727+729+731',
            CatalogoSri::de('f104', 'retencion')->where('codigo', '799')->value('formula'),
        );
    }

    public function test_la_retencion_de_iva_apunta_a_su_casillero_del_104(): void
    {
        $this->assertSame('729', $this->sri->codigo('retencion_iva', '70', 'f104', 'retencion'));
        $this->assertSame('2',   $this->sri->codigo('retencion_iva', '70', 'ats', '11'));
    }
}
