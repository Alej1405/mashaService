<?php

namespace Tests\Feature;

use App\Jobs\GenerarDeclaracion;
use App\Models\AccountPlan;
use App\Models\Declaracion;
use App\Models\Customer;
use App\Models\Empresa;
use App\Models\Gasto;
use App\Models\GastoLinea;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\TipoGasto;
use App\Services\AtsService;
use App\Services\Formulario104Service;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * La generación del 104 y del ATS.
 *
 * Marco: skill `declaraciones-sri-ec`. Lo que se prueba es lo que esa skill
 * exige: que los casilleros salgan del mes y cuadren entre sí, que el mes sin
 * declaración previa avise en vez de arrastrar cero, que compras y gastos vayan
 * al mismo casillero, y que pedir una declaración encole en lugar de bloquear.
 *
 * Corre contra Postgres, no contra el SQLite de phpunit.xml:
 *   DB_CONNECTION=pgsql DB_DATABASE=erp_mashaec_test DB_USERNAME=erp_user \
 *   DB_PASSWORD=… php artisan test --filter=DeclaracionesTest
 */
class DeclaracionesTest extends TestCase
{
    use DatabaseTransactions;

    private Empresa $empresa;
    private Customer $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::create([
            'name' => 'Rivet declara', 'slug' => 'rivet-decl-' . uniqid(),
            'email' => 'decl@rivet.ec', 'activo' => true, 'plan' => 'pro',
            'agente_retencion' => true,
        ]);

        // En el ERP no hay venta sin cliente: el consumidor final es uno más.
        $this->cliente = Customer::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'codigo' => 'CF-' . uniqid(),
            'nombre' => 'CONSUMIDOR FINAL', 'tipo_persona' => 'natural',
            'tipo_identificacion' => 'consumidor_final',
            'numero_identificacion' => \App\Services\AtsService::CONSUMIDOR_FINAL,
            'activo' => true, 'publicado' => false,
        ]);
    }

    /** Una venta de 1.000 con IVA 15 % en agosto de 2026. */
    private int $secuencial = 0;

    private function venta(float $base, bool $conIva = true): Sale
    {
        // Cada venta con su propia referencia: el ERP no admite dos iguales.
        $referencia = sprintf('001-001-%09d', ++$this->secuencial);

        $venta = Sale::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'fecha' => '2026-08-12',
            'customer_id' => $this->cliente->id,
            'referencia' => $referencia, 'tipo_venta' => 'contado',
            'subtotal' => $base, 'iva' => $conIva ? round($base * 0.15, 2) : 0,
            'total' => $base + ($conIva ? round($base * 0.15, 2) : 0),
            'estado' => 'confirmado',
        ]);

        SaleItem::create([
            'sale_id' => $venta->id, 'descripcion_servicio' => 'Licor Pinteño',
            'tipo_item' => 'servicio', 'cantidad' => 1, 'precio_unitario' => $base,
            'aplica_iva' => $conIva, 'subtotal' => $base,
            'iva_monto' => $conIva ? round($base * 0.15, 2) : 0,
            'total' => $base + ($conIva ? round($base * 0.15, 2) : 0),
        ]);

        return $venta;
    }

    /** Un gasto confirmado, que va al mismo casillero que una compra. */
    private function gasto(float $base, float $tarifa = 15): Gasto
    {
        $cuenta = AccountPlan::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'code' => '6.2.01.0' . rand(1, 9),
            'name' => 'Gasto de prueba', 'type' => 'gasto', 'nature' => 'deudora',
            'level' => 4, 'accepts_movements' => true, 'is_active' => true,
        ]);

        $gasto = Gasto::create([
            'empresa_id' => $this->empresa->id, 'fecha' => '2026-08-20',
            'numero_documento' => '001-001-000000050', 'descripcion' => 'Transporte',
            'forma_pago' => 'transferencia', 'estado' => 'confirmado',
        ]);

        GastoLinea::create([
            'gasto_id' => $gasto->id, 'account_plan_id' => $cuenta->id,
            'base' => $base, 'porcentaje_iva' => $tarifa,
            'iva' => round($base * $tarifa / 100, 2), 'deducible' => true,
        ]);

        return $gasto;
    }

    public function test_el_104_sale_de_las_ventas_y_los_gastos_del_mes(): void
    {
        $this->venta(1000);
        $this->gasto(400);

        $r = app(Formulario104Service::class)->calcular($this->empresa->id, 2026, 8);
        $c = $r['casilleros'];

        // Ventas
        $this->assertEquals(1000, $c['401']);
        $this->assertEquals(150, $c['421']);
        $this->assertEquals(150, $c['429']);

        // El gasto entra como adquisición con derecho a crédito tributario.
        $this->assertEquals(400, $c['500']);
        $this->assertEquals(60, $c['520']);

        // Impuesto causado: 150 generado menos 60 de crédito.
        $this->assertEquals(90, $c['601']);
        $this->assertEquals(0, $c['602']);
        $this->assertEquals(90, $c['999']);
    }

    public function test_las_formulas_del_formulario_cuadran_entre_si(): void
    {
        $this->venta(1000);
        $this->venta(500, conIva: false);
        $this->gasto(400);

        $c = app(Formulario104Service::class)->calcular($this->empresa->id, 2026, 8)['casilleros'];

        $this->assertEquals($c['411'] + $c['415'], $c['419'], 'el 419 es la suma de los netos');
        $this->assertEquals($c['483'] + $c['484'], $c['499'], 'el 499 arrastra más lo del mes');
        $this->assertEquals($c['620'] + $c['621'], $c['699']);
        $this->assertEquals($c['699'] + $c['801'], $c['859']);
        $this->assertEquals(round($c['902'] + $c['903'] + $c['904'], 2), $c['999']);

        // Todas las ventas dan derecho a crédito: el factor es 1.
        $this->assertEquals(1.0, $c['563']);
    }

    public function test_sin_el_mes_anterior_avisa_en_vez_de_arrastrar_cero(): void
    {
        $this->venta(1000);

        $r = app(Formulario104Service::class)->calcular($this->empresa->id, 2026, 8);

        $this->assertNotEmpty($r['avisos']);
        $this->assertStringContainsString('07/2026', implode(' ', $r['avisos']));
        $this->assertEquals(0, $r['casilleros']['605']);
    }

    public function test_el_credito_tributario_del_mes_anterior_se_arrastra(): void
    {
        // Julio dejó 80 de crédito tributario por adquisiciones.
        Declaracion::create([
            'empresa_id' => $this->empresa->id, 'tipo' => 'f104',
            'anio' => 2026, 'mes' => 7, 'estado' => 'listo', 'generado_en' => now(),
            'datos' => ['casilleros' => ['615' => 80.0, '617' => 12.0]],
        ]);

        $this->venta(1000);

        $r = app(Formulario104Service::class)->calcular($this->empresa->id, 2026, 8);

        $this->assertEquals(80, $r['casilleros']['605']);
        $this->assertEquals(12, $r['casilleros']['606']);
        $this->assertEmpty(array_filter($r['avisos'], fn ($a) => str_contains($a, 'No hay 104')));
    }

    public function test_el_ats_arma_la_cabecera_y_las_ventas_del_mes(): void
    {
        $this->venta(1000);

        $datos = app(AtsService::class)->datos($this->empresa->id, 2026, 8);

        $this->assertSame('2026', $datos['cabecera']['anio']);
        $this->assertSame('08', $datos['cabecera']['mes']);
        $this->assertCount(1, $datos['ventas']);
        // Sin cliente identificado, el anexo lo reporta como consumidor final.
        $this->assertSame('07', $datos['ventas'][0]['tpIdCliente']);
        $this->assertSame('9999999999999', $datos['ventas'][0]['idCliente']);
        $this->assertSame('1000.00', $datos['ventas'][0]['baseImpGrav']);
    }

    public function test_pedir_una_declaracion_encola_y_devuelve_seguimiento(): void
    {
        Bus::fake();

        $declaracion = Declaracion::create([
            'empresa_id' => $this->empresa->id, 'tipo' => 'f104',
            'anio' => 2026, 'mes' => 8, 'estado' => 'pendiente',
        ]);

        GenerarDeclaracion::dispatch($declaracion->id);

        Bus::assertDispatched(GenerarDeclaracion::class);
        $this->assertNotEmpty($declaracion->seguimiento);
        $this->assertSame('pendiente', $declaracion->estado);
    }

    public function test_el_aviso_llega_a_la_campana_del_panel(): void
    {
        $usuario = \App\Models\User::create([
            'name' => 'Contadora de prueba',
            'email' => 'campana-' . uniqid() . '@mashaec.net',
            'password' => bcrypt('password'),
            'empresa_id' => $this->empresa->id,
        ]);

        $declaracion = Declaracion::create([
            'empresa_id' => $this->empresa->id, 'tipo' => 'f104',
            'anio' => 2026, 'mes' => 8, 'estado' => 'listo', 'generado_en' => now(),
            'datos' => ['casilleros' => ['999' => 90.0]],
        ]);

        $usuario->notify(new \App\Notifications\DeclaracionLista($declaracion));

        // La consulta exacta de Filament. Con `data` en text, en Postgres el
        // operador ->> no existe y la campana revienta con error 500.
        $avisos = $usuario->notifications()->where('data->format', 'filament')->get();

        $this->assertGreaterThan(0, $avisos->count());
        $this->assertStringContainsString('08/2026', $avisos->first()->data['title']);
    }

    public function test_el_job_guarda_los_casilleros_aunque_el_microservicio_no_responda(): void
    {
        Http::fake(['*' => Http::response('', 503)]);

        $this->venta(1000);

        $declaracion = Declaracion::create([
            'empresa_id' => $this->empresa->id, 'tipo' => 'f104',
            'anio' => 2026, 'mes' => 8, 'estado' => 'pendiente',
        ]);

        app(GenerarDeclaracion::class, ['declaracionId' => $declaracion->id])
            ->handle(app(Formulario104Service::class), app(AtsService::class), app(\App\Services\ServicioSri::class));

        $declaracion->refresh();

        $this->assertSame('error', $declaracion->estado);
        // Lo importante: el cálculo no se pierde, el contador puede declarar a mano.
        $this->assertEquals(1000, $declaracion->casillero('401'));
        $this->assertNotEmpty($declaracion->avisos);
    }
}
