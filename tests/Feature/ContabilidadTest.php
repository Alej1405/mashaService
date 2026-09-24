<?php

namespace Tests\Feature;

use App\Models\AccountPlan;
use App\Models\ActivoFijo;
use App\Models\CatalogoSupercias;
use App\Models\Empresa;
use App\Models\Gasto;
use App\Models\GastoLinea;
use App\Models\PorcentajeRetencion;
use App\Models\TipoGasto;
use App\Services\ContabilidadService;
use App\Services\GastoService;
use App\Services\LectorRucPdf;
use App\Services\SuperciasService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * El módulo de contabilidad.
 *
 * Marco: skills `supercias-ec` e `inventarios-contable-ec`. Lo que se prueba es
 * lo que esas skills exigen: que el gasto llegue al asiento con su IVA como
 * crédito tributario, que la retención salga de la tabla vigente a la fecha,
 * que la depreciación sea línea recta y que el archivo de la Superintendencia
 * lleve el código de su catálogo.
 *
 * Corre contra Postgres, no contra el SQLite de phpunit.xml:
 *   DB_CONNECTION=pgsql DB_DATABASE=erp_mashaec_test DB_USERNAME=erp_user \
 *   DB_PASSWORD=… php artisan test --filter=ContabilidadTest
 */
class ContabilidadTest extends TestCase
{
    use DatabaseTransactions;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::create([
            'name' => 'Rivet contable', 'slug' => 'rivet-conta-' . uniqid(),
            'email' => 'conta@rivet.ec', 'activo' => true, 'plan' => 'pro',
            'agente_retencion' => true,
        ]);

        foreach ([
            ['1.1.02.05', 'IVA crédito tributario', 'activo', 'deudora'],
            ['2.1.01.01', 'Cuentas por pagar proveedores', 'pasivo', 'acreedora'],
            ['2.1.03.01', 'Retenciones por pagar', 'pasivo', 'acreedora'],
            ['6.2.01.01', 'Gasto de alimentación', 'gasto', 'deudora'],
        ] as [$code, $name, $type, $nature]) {
            AccountPlan::withoutGlobalScopes()->create([
                'empresa_id' => $this->empresa->id, 'code' => $code, 'name' => $name,
                'type' => $type, 'nature' => $nature, 'level' => 4,
                'accepts_movements' => true, 'is_active' => true,
            ]);
        }
    }

    private function cuenta(string $code): AccountPlan
    {
        return AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $this->empresa->id)->where('code', $code)->firstOrFail();
    }

    public function test_el_gasto_se_contabiliza_con_iva_como_credito_tributario(): void
    {
        $tipo = TipoGasto::create([
            'nombre' => 'Alimentación de prueba',
            'account_plan_id' => $this->cuenta('6.2.01.01')->id,
            'concepto_retencion' => 'Servicios con predominio de mano de obra',
            'deducible' => true, 'activo' => true,
        ]);

        $gasto = Gasto::create([
            'empresa_id' => $this->empresa->id, 'fecha' => '2026-09-10',
            'numero_documento' => '001-001-000000001', 'descripcion' => 'Almuerzos del equipo',
        ]);

        GastoLinea::create([
            'gasto_id' => $gasto->id, 'tipo_gasto_id' => $tipo->id,
            'account_plan_id' => $this->cuenta('6.2.01.01')->id,
            'base' => 100, 'porcentaje_iva' => 15, 'iva' => 15, 'deducible' => true,
        ]);

        $confirmado = app(GastoService::class)->confirmar($gasto);

        $this->assertEquals(100, (float) $confirmado->subtotal);
        $this->assertEquals(15, (float) $confirmado->iva);
        $this->assertEquals(115, (float) $confirmado->total);
        $this->assertEquals('confirmado', $confirmado->estado);
        $this->assertNotNull($confirmado->journal_entry_id);

        $asiento = $confirmado->journalEntry;
        $this->assertTrue((bool) $asiento->esta_cuadrado);
        $this->assertEquals((float) $asiento->total_debe, (float) $asiento->total_haber);

        // El IVA no es gasto: va a crédito tributario.
        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $asiento->id,
            'account_plan_id'  => $this->cuenta('1.1.02.05')->id,
            'debe'             => 15,
        ]);
    }

    public function test_la_retencion_usa_el_porcentaje_vigente_a_la_fecha(): void
    {
        // Mano de obra: 3 % desde el 1 de marzo de 2026 (NAC-DGERCGC26-00000009)
        $calculo = app(ContabilidadService::class)->calcularRetencion(
            baseImponible: 100, iva: 15, fecha: '2026-09-10',
            conceptoRenta: 'Servicios con predominio de mano de obra',
            conceptoIva: 'Servicios, comisiones y consultoría',
        );

        $this->assertEquals(3.0, $calculo['renta']);
        // IVA servicios: 70 % sobre el IVA, no sobre la base.
        $this->assertEquals(10.5, $calculo['iva']);
        $this->assertEquals(13.5, $calculo['total']);
    }

    public function test_una_retencion_de_antes_de_marzo_no_existe_todavia(): void
    {
        $p = PorcentajeRetencion::para('renta', 'Servicios profesionales prestados por sociedades', '2025-12-31');

        $this->assertNull($p, 'esa categoría nació con la resolución de 2026');
    }

    public function test_la_depreciacion_es_linea_recta_y_no_se_duplica(): void
    {
        // El activo fijo es un ítem de inventario: no hay tabla aparte.
        AccountPlan::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'code' => '1.2.09.01',
            'name' => '(-) Depreciación acumulada PPE', 'type' => 'activo',
            'nature' => 'acreedora', 'level' => 4,
            'accepts_movements' => true, 'is_active' => true,
        ]);

        $activo = ActivoFijo::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'nombre' => 'Marmita 200 L',
            'codigo' => 'MAQ-' . uniqid(), 'type' => ActivoFijo::TIPO,
            'fecha_compra' => '2026-01-15', 'purchase_price' => 4800, 'costo_promedio' => 4800,
            'stock_actual' => 1, 'valor_residual' => 0, 'vida_util_meses' => 120,
            'cuenta_gasto_id' => $this->cuenta('6.2.01.01')->id, 'activo' => true,
        ]);

        $this->assertEquals(40.0, $activo->cuota_mensual);

        $primera = app(GastoService::class)->depreciarMes($this->empresa->id, 2026, 9);
        $this->assertEquals(1, $primera['activos']);
        $this->assertEquals(40.0, $primera['total']);

        // Correrlo dos veces en el mismo mes no vuelve a depreciar.
        $segunda = app(GastoService::class)->depreciarMes($this->empresa->id, 2026, 9);
        $this->assertEquals(0, $segunda['activos']);

        $this->assertEquals(40.0, (float) $activo->fresh()->depreciacion_acumulada);
    }

    public function test_el_archivo_de_supercias_sale_con_el_codigo_del_catalogo(): void
    {
        $contenido = app(SuperciasService::class)->archivo($this->empresa->id, 2026, 'situacion_financiera');
        $lineas = array_filter(explode("\n", $contenido));

        $this->assertCount(376, $lineas, 'el archivo lleva todas las líneas del catálogo oficial');
        $this->assertMatchesRegularExpression('/^1 \d+\.\d{2}$/', $lineas[0]);
        $this->assertGreaterThan(0, CatalogoSupercias::where('estado', 'situacion_financiera')->count());
    }

    public function test_el_certificado_de_ruc_completa_la_ficha(): void
    {
        $pdf = '/Users/mashaec/Downloads/RCR1755627352278289_ruc.pdf';

        if (! file_exists($pdf)) {
            $this->markTestSkipped('el certificado de ejemplo no está en esta máquina');
        }

        $datos = app(LectorRucPdf::class)->leer($pdf);

        $this->assertSame('1793229485001', $datos['ruc']);
        $this->assertSame('SOCIEDADES', $datos['tipo_contribuyente']);
        $this->assertSame('GENERAL', $datos['regimen']);
        $this->assertTrue($datos['obligado_contabilidad']);
        // El RUC dice que NO es agente de retención: el documento manda.
        $this->assertFalse($datos['agente_retencion']);
        $this->assertCount(2, $datos['actividades']);
    }
}
