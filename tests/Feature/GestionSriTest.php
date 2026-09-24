<?php

namespace Tests\Feature;

use App\Models\AccountPlan;
use App\Models\ComprobanteSri;
use App\Models\Declaracion;
use App\Models\Empresa;
use App\Models\FirmaElectronica;
use App\Services\Formulario104Service;
use App\Services\GestionSriService;
use App\Services\ImportadorComprobantesSri;
use App\Services\MapeoSuperciasService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Inicio de gestión, comprobantes del portal y firma electrónica.
 *
 * Marco: skills `declaraciones-sri-ec` y `supercias-ec`. Lo que se prueba es lo
 * que hace falta para poder afirmar que una empresa está al día: desde cuándo
 * responde el ERP, qué períodos faltan, que lo importado del portal no se
 * cuente dos veces, y que la firma avise antes de caducar y no después.
 *
 * Corre contra Postgres, no contra el SQLite de phpunit.xml:
 *   DB_CONNECTION=pgsql DB_DATABASE=erp_mashaec_test DB_USERNAME=erp_user \
 *   DB_PASSWORD=… php artisan test --filter=GestionSriTest
 */
class GestionSriTest extends TestCase
{
    use DatabaseTransactions;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::create([
            'name' => 'Rivet gestión', 'slug' => 'rivet-gest-' . uniqid(),
            'email' => 'gest@rivet.ec', 'activo' => true, 'plan' => 'pro',
            'numero_identificacion' => '1793229485001',   // noveno dígito: 8 → vence el 24
            'inicio_gestion_erp' => '2026-06-01',
        ]);
    }

    public function test_solo_se_responde_desde_el_inicio_de_gestion(): void
    {
        $periodos = app(GestionSriService::class)->periodos($this->empresa->id, 24);

        $this->assertNotEmpty($periodos);

        $masAntiguo = end($periodos);
        $this->assertSame(2026, $masAntiguo['anio']);
        $this->assertSame(6, $masAntiguo['mes'], 'no se piden períodos anteriores al inicio de gestión');
    }

    public function test_el_vencimiento_sale_del_noveno_digito_del_ruc(): void
    {
        // RUC 1793229485001: el noveno dígito es 8, así que vence el 24.
        $vence = app(GestionSriService::class)->vencimiento($this->empresa, 2026, 7);

        $this->assertSame('24/08/2026', $vence->format('d/m/Y'), 'el 104 de julio vence en agosto');
    }

    public function test_un_mes_sin_nada_se_reconoce_como_vacio_y_se_declara_igual(): void
    {
        $movimiento = app(GestionSriService::class)->hayMovimiento($this->empresa->id, 2026, 7);

        $this->assertTrue($movimiento['vacio']);
        $this->assertSame(0, $movimiento['ventas']);
        $this->assertSame(0, $movimiento['compras']);

        // Y aun así el período aparece en la lista: lo que se sanciona es la omisión.
        $periodos = app(GestionSriService::class)->periodos($this->empresa->id, 24);
        $this->assertContains(7, array_column($periodos, 'mes'));
    }

    public function test_generado_descargado_y_presentado_son_tres_estados_distintos(): void
    {
        $declaracion = Declaracion::create([
            'empresa_id' => $this->empresa->id, 'tipo' => 'f104',
            'anio' => 2026, 'mes' => 7, 'estado' => 'listo',
            'archivo' => 'declaraciones/x.xml', 'generado_en' => now(),
        ]);

        $estado = fn () => collect(app(GestionSriService::class)->periodos($this->empresa->id, 24))
            ->firstWhere('mes', 7)['estado']['clave'];

        $this->assertSame('generado', $estado());

        $declaracion->update(['descargado_en' => now()]);
        $this->assertSame('descargado', $estado());

        $declaracion->update(['presentado_en' => now()]);
        $this->assertSame('presentado', $estado());
    }

    public function test_el_mismo_archivo_importado_dos_veces_no_duplica_el_mes(): void
    {
        Http::fake([
            '*/comprobantes/leer' => Http::response([
                'tipo' => 'recibidos',
                'comprobantes' => [[
                    'origen' => 'recibido', 'clave_acceso' => str_repeat('1', 49),
                    'tipo_comprobante' => '01', 'identificacion' => '0991331859001',
                    'razon_social' => 'ATIMASA S.A.', 'fecha_emision' => '2026-07-15',
                    'numero' => '198-052-000241796', 'establecimiento' => '198',
                    'punto_emision' => '052', 'secuencial' => '000241796',
                    'base_gravada' => '100.00', 'base_cero' => '0.00',
                    'iva' => '15.00', 'total' => '115.00', 'desglose' => 'declarado',
                ]],
                'periodos' => [], 'avisos' => [],
            ]),
        ]);

        $importador = app(ImportadorComprobantesSri::class);

        $primera = $importador->importar($this->empresa->id, 'contenido');
        $segunda = $importador->importar($this->empresa->id, 'contenido');

        $this->assertSame(1, $primera['importados']);
        $this->assertSame(0, $segunda['importados'], 'la clave de acceso impide contarlo dos veces');
        $this->assertSame(1, $segunda['repetidos']);
        $this->assertSame(1, ComprobanteSri::where('empresa_id', $this->empresa->id)->count());
    }

    public function test_lo_importado_del_portal_llega_al_formulario_104(): void
    {
        ComprobanteSri::create([
            'empresa_id' => $this->empresa->id, 'clave_acceso' => str_repeat('2', 49),
            'origen' => 'recibido', 'tipo_comprobante' => '01', 'identificacion' => '0991331859001',
            'fecha_emision' => '2026-07-20', 'numero' => '001-001-000000001',
            'base_gravada' => 400, 'base_cero' => 0, 'iva' => 60, 'total' => 460,
            'desglose' => 'declarado', 'importado_en' => now(),
        ]);

        $r = app(Formulario104Service::class)->calcular($this->empresa->id, 2026, 7);

        $this->assertEquals(400, $r['casilleros']['500']);
        $this->assertEquals(60, $r['casilleros']['520']);
        $this->assertEquals(400, $r['casilleros']['509']);
        $this->assertStringContainsString('portal del SRI', implode(' ', $r['avisos']));
    }

    public function test_lo_conciliado_con_el_erp_no_se_suma_otra_vez(): void
    {
        ComprobanteSri::create([
            'empresa_id' => $this->empresa->id, 'clave_acceso' => str_repeat('3', 49),
            'origen' => 'recibido', 'tipo_comprobante' => '01', 'identificacion' => '0991331859001',
            'fecha_emision' => '2026-07-20', 'numero' => '001-001-000000002',
            'base_gravada' => 999, 'iva' => 149.85, 'total' => 1148.85,
            'desglose' => 'declarado', 'importado_en' => now(),
            // ya está registrado como compra en el ERP
            'conciliado_con' => 'purchase', 'conciliado_id' => 1,
        ]);

        $r = app(Formulario104Service::class)->calcular($this->empresa->id, 2026, 7);

        $this->assertEquals(0, $r['casilleros']['500'], 'lo que ya está en el ERP no se cuenta dos veces');
    }

    public function test_antes_del_inicio_de_gestion_no_se_reclama_el_arrastre(): void
    {
        // Junio es el primer mes: no hay mayo que arrastrar y avisarlo sería ruido.
        $junio = app(Formulario104Service::class)->calcular($this->empresa->id, 2026, 6);
        $julio = app(Formulario104Service::class)->calcular($this->empresa->id, 2026, 7);

        $this->assertEmpty(array_filter($junio['avisos'], fn ($a) => str_contains($a, 'No hay 104')));
        $this->assertNotEmpty(array_filter($julio['avisos'], fn ($a) => str_contains($a, 'No hay 104')),
            'julio sí debe reclamar el 104 de junio, que ya está dentro de la gestión');
    }

    public function test_la_firma_avisa_antes_de_caducar_no_despues(): void
    {
        $firma = FirmaElectronica::create([
            'empresa_id' => $this->empresa->id, 'archivo' => 'firmas/x.p12', 'clave' => 'secreta',
            'titular' => 'PABLO REVILLA', 'valido_desde' => now()->subYears(2)->addDays(30),
            'valido_hasta' => now()->addDays(30), 'activa' => true,
        ]);

        $this->assertSame(30, $firma->dias_restantes);
        $this->assertSame('wa', $firma->estado['tono'], 'a 30 días ya debe estar en amarillo');
        $this->assertTrue($firma->vigente);

        $caducada = FirmaElectronica::create([
            'empresa_id' => $this->empresa->id, 'archivo' => 'firmas/y.p12', 'clave' => 'secreta',
            'valido_hasta' => now()->subDay(), 'activa' => true,
        ]);

        $this->assertSame('da', $caducada->estado['tono']);
        $this->assertFalse($caducada->vigente);
    }

    public function test_la_clave_de_la_firma_no_se_guarda_en_claro(): void
    {
        $firma = FirmaElectronica::create([
            'empresa_id' => $this->empresa->id, 'archivo' => 'firmas/z.p12',
            'clave' => 'mi-clave-secreta', 'valido_hasta' => now()->addYear(), 'activa' => true,
        ]);

        $guardado = \Illuminate\Support\Facades\DB::table('firmas_electronicas')
            ->where('id', $firma->id)->value('clave');

        $this->assertNotSame('mi-clave-secreta', $guardado);
        $this->assertSame('mi-clave-secreta', $firma->claveEnClaro());
        $this->assertArrayNotHasKey('clave', $firma->toArray());
    }

    /** @return AccountPlan */
    private function cuenta(string $code, string $name, string $type = 'activo'): AccountPlan
    {
        return AccountPlan::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'code' => $code, 'name' => $name,
            'type' => $type, 'nature' => 'deudora', 'level' => 4,
            'accepts_movements' => true, 'is_active' => true,
        ]);
    }

    public function test_una_cuenta_nueva_nace_con_su_linea_del_estado(): void
    {
        // Sin pedir nada: el observer la mapea al crearla.
        $caja = $this->cuenta('1.1.01.01', 'Caja general');

        $this->assertSame('1010101', $caja->fresh()->codigo_supercias,
            'Caja general es la línea CAJA del catálogo');
        $this->assertFalse((bool) $caja->fresh()->codigo_supercias_revisado);
    }

    public function test_ninguna_cuenta_se_queda_sin_linea_del_estado(): void
    {
        // Un nombre que no se parece a nada del catálogo: aun así cae en su grupo.
        $this->cuenta('6.9.01', 'Zumbido interplanetario', 'gasto');
        $this->cuenta('1.1.09.01', 'Chiribitas varias');

        $r = app(MapeoSuperciasService::class)->mapearTodo($this->empresa->id);

        $this->assertSame(0, $r['sin_mapear'], 'una cuenta sin línea desaparece del balance');
        $this->assertSame(0, app(\App\Services\SuperciasService::class)
            ->cuentasSinCodigo($this->empresa->id));
    }

    public function test_lo_dudoso_queda_marcado_para_que_alguien_lo_mire(): void
    {
        $exacta = $this->cuenta('1.1.04.01', 'Seguros pagados por anticipado');
        $vaga   = $this->cuenta('6.9.02', 'Zumbido interplanetario', 'gasto');

        $servicio = app(MapeoSuperciasService::class);
        $porRevisar = $servicio->paraRevisar($this->empresa->id)->pluck('cuenta.code')->all();

        $this->assertContains('6.9.02', $porRevisar, 'lo que se asignó por estructura se revisa');
        $this->assertNotContains('1.1.04.01', $porRevisar, 'lo que casó por nombre no molesta al contador');
        $this->assertGreaterThanOrEqual(
            MapeoSuperciasService::CONFIANZA_ALTA,
            (int) $exacta->fresh()->codigo_supercias_confianza,
        );
    }

    public function test_lo_que_el_contador_confirma_no_lo_vuelve_a_tocar_el_automatico(): void
    {
        $cuenta = $this->cuenta('6.9.03', 'Zumbido interplanetario', 'gasto');
        $servicio = app(MapeoSuperciasService::class);

        // Un código que no existe no se aplica aunque lo pidan.
        $this->assertSame(0, $servicio->aplicar($this->empresa->id, [$cuenta->id => '99999999']));

        $this->assertSame(1, $servicio->aplicar($this->empresa->id, [$cuenta->id => '5020111']));

        $cuenta->refresh();
        $this->assertSame('5020111', $cuenta->codigo_supercias);
        $this->assertTrue((bool) $cuenta->codigo_supercias_revisado);
        $this->assertSame(100, (int) $cuenta->codigo_supercias_confianza);

        // Rehacer las propuestas no pisa lo revisado.
        $servicio->mapearTodo($this->empresa->id, incluirRevisadas: true);
        $this->assertSame('5020111', $cuenta->fresh()->codigo_supercias);
    }
}
