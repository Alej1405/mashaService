<?php

namespace Tests\Feature;

use App\Models\AccountPlan;
use App\Models\CashRegister;
use App\Models\ComprobanteSri;
use App\Models\Empresa;
use App\Models\Gasto;
use App\Models\InventoryItem;
use App\Models\JournalEntryLine;
use App\Models\ProductoProveedor;
use App\Models\Purchase;
use App\Modules\Compras\Actions\ConfirmarCompraElectronica;
use App\Models\TipoGasto;
use App\Services\AtsService;
use App\Services\ClasificadorComprobantesService;
use App\Services\ServicioSri;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Del portal del SRI a la contabilidad y al ATS, de punta a punta.
 *
 * Una factura del listado de recibidos se registra en la bandeja: se trae su XML
 * por la clave de acceso, se parte por tarifa, se asienta contra el medio con que
 * se pagó y su clave queda como autorización. El ATS del mes tiene que decir lo
 * mismo que el asiento.
 *
 * Corre contra Postgres:
 *   DB_CONNECTION=pgsql DB_DATABASE=erp_mashaec_test DB_USERNAME=erp_user \
 *   DB_PASSWORD=… php artisan test --filter=PortalAContabilidadTest
 */
class PortalAContabilidadTest extends TestCase
{
    use DatabaseTransactions;

    private const CLAVE = '1009202601179001234500120010010000001231234567810';

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::create([
            'name' => 'Rivet portal', 'slug' => 'rivet-portal-' . uniqid(),
            'email' => 'portal@rivet.ec', 'activo' => true, 'plan' => 'pro',
            'numero_identificacion' => '1793229485001',
            'inicio_gestion_erp' => '2026-01-01',
        ]);

        $this->cuenta('1.1.01.01', 'Caja general', 'activo');
        $this->cuenta('1.1.02.05', 'IVA crédito tributario', 'activo');
        $this->cuenta('2.1.01.01', 'Proveedores locales', 'pasivo', 'acreedora');

        // El SRI devuelve el XML: 100 al 15 % y 20 a tarifa cero.
        $this->mock(ServicioSri::class, fn ($m) => $m->shouldReceive('autorizacionComprobante')->andReturn([
            'ok' => true, 'estado' => 'AUTORIZADO',
            'comprobante' => [
                'importe_total' => '135.00',
                'impuestos' => [
                    ['codigo' => '2', 'codigo_porcentaje' => '4', 'base_imponible' => '100.00', 'valor' => '15.00'],
                    ['codigo' => '2', 'codigo_porcentaje' => '0', 'base_imponible' => '20.00', 'valor' => '0.00'],
                ],
            ],
        ]));
    }

    private function cuenta(string $code, string $name, string $type, string $nature = 'deudora'): AccountPlan
    {
        return AccountPlan::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'code' => $code, 'name' => $name,
            'type' => $type, 'nature' => $nature, 'level' => 4,
            'accepts_movements' => true, 'is_active' => true,
        ]);
    }

    private function comprobante(string $clave = self::CLAVE, string $numero = '001-001-000000123'): ComprobanteSri
    {
        // Como llega del listado: base total y IVA, sin separar la tarifa cero.
        return ComprobanteSri::create([
            'empresa_id' => $this->empresa->id, 'clave_acceso' => $clave, 'origen' => 'recibido',
            'tipo_comprobante' => '01', 'identificacion' => '1790012345001',
            'razon_social' => 'Insumos del Valle S.A.', 'fecha_emision' => '2026-09-10',
            'numero' => $numero, 'establecimiento' => '001', 'punto_emision' => '001',
            'secuencial' => substr($numero, -9), 'base_gravada' => 120, 'base_cero' => 0,
            'iva' => 15, 'total' => 135, 'desglose' => 'declarado', 'importado_en' => now(),
        ]);
    }

    public function test_un_gasto_del_portal_queda_con_autorizacion_tarifas_y_su_medio_de_pago(): void
    {
        $caja = CashRegister::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'nombre' => 'Caja de prueba', 'tipo' => 'principal',
            'saldo_actual' => 0, 'activo' => true,
        ]);
        $tipo = TipoGasto::create([
            'nombre' => 'Insumos de limpieza',
            'account_plan_id' => $this->cuenta('6.1.01.01', 'Gasto de insumos', 'gasto')->id,
            'deducible' => true, 'activo' => true,
        ]);

        $c = $this->comprobante();
        $r = app(ClasificadorComprobantesService::class)->clasificar($c, [
            'destino' => 'gasto', 'tipo_gasto_id' => $tipo->id, 'pago' => "efectivo:{$caja->id}",
        ]);

        $this->assertTrue($r['ok'], $r['error'] ?? '');
        $gasto = Gasto::with('lineas')->findOrFail($r['id']);

        $this->assertSame(self::CLAVE, $gasto->autorizacion, 'la clave de acceso es la autorización del ATS');
        $this->assertSame('efectivo', $gasto->forma_pago);
        $this->assertCount(2, $gasto->lineas, 'una línea por tarifa');
        $this->assertEquals(100, $gasto->lineas->firstWhere('iva', '>', 0)->base);
        $this->assertEquals(20, $gasto->lineas->firstWhere('iva', 0)->base);

        $c->refresh();
        $this->assertSame('xml', $c->desglose, 'el desglose sale del XML del SRI');
        $this->assertEquals(20, $c->base_cero);

        // Pagado en efectivo: el haber va contra la caja, no contra proveedores.
        $haber = JournalEntryLine::where('journal_entry_id', $gasto->journal_entry_id)->where('haber', '>', 0)->get();
        $this->assertCount(1, $haber);
        $this->assertSame($caja->account_plan_id, $haber->first()->account_plan_id);
        $this->assertEquals(135, $haber->first()->haber);

        // La caja baja su saldo aunque quede en negativo.
        $this->assertEquals(-135, (float) $caja->fresh()->saldo_actual);

        // El ATS dice lo mismo que el asiento.
        $ats = app(AtsService::class)->datos($this->empresa->id, 2026, 9);
        $compra = collect($ats['compras'])->firstWhere('secuencial', '000000123');

        $this->assertSame('1793229485001', $ats['cabecera']['IdInformante']);
        $this->assertSame(self::CLAVE, $compra['autorizacion']);
        $this->assertSame('100.00', $compra['baseImpGrav']);
        $this->assertSame('20.00', $compra['baseImponible']);
        $this->assertSame('15.00', $compra['montoIva']);
        $this->assertSame('01', $compra['codSustento'], 'gasto con IVA: crédito tributario');
    }

    public function test_el_ats_avisa_las_facturas_del_portal_sin_registrar(): void
    {
        $this->comprobante('1009202601179001234500120010010000009991234567811', '001-001-000000999');

        $ats = app(AtsService::class)->datos($this->empresa->id, 2026, 9);

        $this->assertNotEmpty(array_filter($ats['avisos'], fn ($a) => str_contains($a, 'no están registradas')),
            'lo que suma al 104 y no está en el ATS se dice, no se calla');
    }

    public function test_la_factura_se_registra_con_su_detalle_y_cada_producto_se_dice_una_sola_vez(): void
    {
        $caja = CashRegister::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'nombre' => 'Caja detalle', 'tipo' => 'principal',
            'saldo_actual' => 0, 'activo' => true,
        ]);
        $tipo = TipoGasto::create([
            'nombre' => 'Limpieza', 'account_plan_id' => $this->cuenta('6.1.01.02', 'Limpieza', 'gasto')->id,
            'deducible' => true, 'activo' => true,
        ]);

        // El mapeo del IVA en compras, que toda empresa trae de su configuración contable.
        \App\Models\AccountingMap::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'tipo_item' => 'global', 'tipo_movimiento' => 'iva_compras',
            'account_plan_id' => AccountPlan::withoutGlobalScopes()->where('empresa_id', $this->empresa->id)
                ->where('code', '1.1.02.05')->value('id'),
        ]);

        // El XML autorizado: dos productos, uno al 15 % y otro a tarifa cero.
        $this->mock(ServicioSri::class, fn ($m) => $m->shouldReceive('autorizacionComprobante')->andReturn([
            'ok' => true, 'estado' => 'AUTORIZADO',
            'comprobante' => [
                'tipo' => 'factura', 'clave_acceso' => self::CLAVE, 'numero' => '001-001-000000123',
                'fecha_emision' => '10/09/2026', 'importe_total' => '135.00',
                'emisor' => ['ruc' => '1790012345001', 'razon_social' => 'Insumos del Valle S.A.'],
                'comprador' => ['identificacion' => '1793229485001', 'razon_social' => 'Rivet'],
                'detalles' => [
                    ['codigo' => 'LIMP-01', 'descripcion' => 'Desinfectante galón', 'cantidad' => '2',
                     'precio_unitario' => '50.00', 'subtotal' => '100.00',
                     'impuestos' => [['codigo' => '2', 'codigo_porcentaje' => '4', 'base_imponible' => '100.00', 'valor' => '15.00']]],
                    ['codigo' => 'FUN-02', 'descripcion' => 'Fundas de basura', 'cantidad' => '4',
                     'precio_unitario' => '5.00', 'subtotal' => '20.00',
                     'impuestos' => [['codigo' => '2', 'codigo_porcentaje' => '0', 'base_imponible' => '20.00', 'valor' => '0.00']]],
                ],
                'impuestos' => [
                    ['codigo' => '2', 'codigo_porcentaje' => '4', 'base_imponible' => '100.00', 'valor' => '15.00'],
                    ['codigo' => '2', 'codigo_porcentaje' => '0', 'base_imponible' => '20.00', 'valor' => '0.00'],
                ],
                'pagos' => [['codigo' => '20', 'total' => '135.00']],
            ],
        ]));

        $c = $this->comprobante();
        $r = app(ClasificadorComprobantesService::class)->registrarConDetalle($c, "efectivo:{$caja->id}");

        $this->assertTrue($r['ok'], $r['error'] ?? '');
        $this->assertSame('pendiente', $r['estado'], 'productos nuevos: esperan a que se diga qué son');

        $compra = Purchase::withoutGlobalScopes()->with('items')->findOrFail($r['id']);
        $this->assertCount(2, $compra->items, 'las líneas salen de la factura, no de un select');
        $this->assertSame(self::CLAVE, $compra->clave_acceso);
        $this->assertSame('efectivo', $compra->forma_pago, 'manda el pago que eligió quien registra');

        // Se dice una vez qué es cada producto y la compra se registra sola.
        foreach (ProductoProveedor::withoutGlobalScopes()->where('empresa_id', $this->empresa->id)->get() as $producto) {
            app(ConfirmarCompraElectronica::class)->producto($producto, null, $tipo->id, null);
        }

        $compra->refresh();
        $this->assertSame('confirmado', $compra->status, (string) $compra->error_contable_msg);
        $this->assertNotNull($compra->journal_entry_id);

        $ats = app(AtsService::class)->datos($this->empresa->id, 2026, 9);
        $registro = collect($ats['compras'])->firstWhere('secuencial', '000000123');
        $this->assertSame(self::CLAVE, $registro['autorizacion']);
        $this->assertSame('100.00', $registro['baseImpGrav']);
        $this->assertSame('20.00', $registro['baseImponible']);
        $this->assertSame('01', $registro['codSustento'], 'una compra que solo trae servicios o gastos no es inventario');
    }

    public function test_el_iva_que_manda_es_el_de_la_factura_no_el_redondeo_de_las_lineas(): void
    {
        // 69,57 × 15 % = 10,4355: la factura dice 10,44; la línea, redondeada sola, 10,43.
        $this->mock(ServicioSri::class, fn ($m) => $m->shouldReceive('autorizacionComprobante')->andReturn([
            'ok' => true, 'estado' => 'AUTORIZADO',
            'comprobante' => [
                'tipo' => 'factura', 'clave_acceso' => self::CLAVE, 'numero' => '001-001-000000123',
                'fecha_emision' => '10/09/2026', 'importe_total' => '80.01',
                'emisor' => ['ruc' => '1717729485001', 'razon_social' => 'Fumigaciones'],
                'comprador' => ['identificacion' => '1793229485001'],
                'detalles' => [['codigo' => 'PLAGA', 'descripcion' => 'Control de plagas', 'cantidad' => '1',
                    'precio_unitario' => '69.57', 'subtotal' => '69.57',
                    'impuestos' => [['codigo' => '2', 'codigo_porcentaje' => '4', 'base_imponible' => '69.57', 'valor' => '10.43']]]],
                'impuestos' => [['codigo' => '2', 'codigo_porcentaje' => '4', 'base_imponible' => '69.57', 'valor' => '10.44']],
                'pagos' => [],
            ],
        ]));

        $c = $this->comprobante();
        $r = app(ClasificadorComprobantesService::class)->registrarConDetalle($c, 'credito');
        $compra = Purchase::withoutGlobalScopes()->with('items')->findOrFail($r['id']);

        $this->assertEquals(10.44, (float) $compra->iva);
        $this->assertEquals(80.01, (float) $compra->total, 'la cuenta por pagar es la de la factura');
        $this->assertEquals(10.44, (float) $compra->items->first()->iva_monto);
    }

    public function test_la_base_que_manda_es_la_de_la_cabecera_cuando_el_xml_se_contradice(): void
    {
        // Disproquim: la línea dice 4,17 × 2,2609 = 9,43; la cabecera, 9,42 y total 10,83.
        $this->mock(ServicioSri::class, fn ($m) => $m->shouldReceive('autorizacionComprobante')->andReturn([
            'ok' => true, 'estado' => 'AUTORIZADO',
            'comprobante' => [
                'tipo' => 'factura', 'clave_acceso' => self::CLAVE, 'numero' => '001-001-000000123',
                'fecha_emision' => '10/09/2026', 'importe_total' => '10.83',
                'emisor' => ['ruc' => '1792224659001', 'razon_social' => 'Disproquim'],
                'comprador' => ['identificacion' => '1793229485001'],
                'detalles' => [['codigo' => '0000172', 'descripcion' => 'Glucosa', 'cantidad' => '4.17',
                    'precio_unitario' => '2.260900', 'subtotal' => '9.43',
                    'impuestos' => [['codigo' => '2', 'codigo_porcentaje' => '4', 'base_imponible' => '9.43', 'valor' => '1.41']]]],
                'impuestos' => [['codigo' => '2', 'codigo_porcentaje' => '4', 'base_imponible' => '9.42', 'valor' => '1.41']],
                'pagos' => [['codigo' => '01', 'total' => '10.83']],
            ],
        ]));

        $r = app(ClasificadorComprobantesService::class)->registrarConDetalle($this->comprobante(), 'credito');
        $compra = Purchase::withoutGlobalScopes()->with('items')->findOrFail($r['id']);

        $this->assertEquals(9.42, (float) $compra->subtotal);
        $this->assertEquals(10.83, (float) $compra->total, 'lo que consta en el SRI');
        $this->assertEquals(9.42, (float) $compra->items->first()->subtotal);
    }

    public function test_si_el_sri_ya_no_la_devuelve_se_pide_a_mano(): void
    {
        $this->mock(ServicioSri::class, fn ($m) => $m->shouldReceive('autorizacionComprobante')
            ->andReturn(['ok' => false, 'estado' => 'NO_ENCONTRADO']));

        $c = $this->comprobante();
        $r = app(ClasificadorComprobantesService::class)->registrarConDetalle($c, 'credito');

        $this->assertFalse($r['ok']);
        $this->assertTrue($r['manual']);
        $this->assertSame('sin_xml', $c->fresh()->desglose);
    }

    public function test_registrar_todas_corre_en_la_cola_y_deja_el_avance(): void
    {
        $this->mock(ServicioSri::class, fn ($m) => $m->shouldReceive('autorizacionComprobante')
            ->andReturn(['ok' => false, 'estado' => 'NO_ENCONTRADO']));
        $this->comprobante();

        \App\Jobs\RegistrarComprobantesDelPortal::dispatchSync($this->empresa->id, null);

        $avance = \Illuminate\Support\Facades\Cache::get(
            \App\Jobs\RegistrarComprobantesDelPortal::claveAvance($this->empresa->id));
        $this->assertFalse($avance['corriendo']);
        $this->assertSame(1, $avance['total']);
        $this->assertSame(1, $avance['manuales'], 'lo que el SRI ya no devuelve queda para decirlo a mano');

        // Con un año elegido, solo se trabaja ese año.
        \App\Jobs\RegistrarComprobantesDelPortal::dispatchSync($this->empresa->id, null, [], 2025);
        $this->assertSame(0, \Illuminate\Support\Facades\Cache::get(
            \App\Jobs\RegistrarComprobantesDelPortal::claveAvance($this->empresa->id))['total']);
    }

    public function test_una_factura_vieja_se_registra_con_su_xml_si_esta_en_el_listado(): void
    {
        \App\Models\AccountingMap::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'tipo_item' => 'global', 'tipo_movimiento' => 'iva_compras',
            'account_plan_id' => AccountPlan::withoutGlobalScopes()->where('empresa_id', $this->empresa->id)
                ->where('code', '1.1.02.05')->value('id'),
        ]);

        // El XML suelto, sin el sobre de autorización: el lector no sabe si se autorizó.
        $this->mock(ServicioSri::class, fn ($m) => $m->shouldReceive('leerXmlComprobante')->andReturn([
            'ok' => true, 'estado' => 'SIN_AUTORIZACION_ADJUNTA',
            'comprobante' => [
                'tipo' => 'factura', 'clave_acceso' => self::CLAVE, 'numero' => '001-001-000000123',
                'fecha_emision' => '10/09/2026', 'importe_total' => '135.00',
                'emisor' => ['ruc' => '1790012345001', 'razon_social' => 'Insumos del Valle S.A.'],
                'comprador' => ['identificacion' => '1793229485001'],
                'detalles' => [['codigo' => 'LIMP-01', 'descripcion' => 'Desinfectante galón', 'cantidad' => '2',
                    'precio_unitario' => '50.00', 'subtotal' => '100.00',
                    'impuestos' => [['codigo' => '2', 'codigo_porcentaje' => '4', 'base_imponible' => '100.00', 'valor' => '15.00']]]],
                'impuestos' => [['codigo' => '2', 'codigo_porcentaje' => '4', 'base_imponible' => '100.00', 'valor' => '15.00']],
                'pagos' => [],
            ],
        ]));

        $servicio = app(ClasificadorComprobantesService::class);

        // Sin el listado del portal no hay prueba de que el SRI la autorizó.
        $sinListado = $servicio->registrarDesdeXml($this->empresa->id, '<factura/>', 'credito');
        $this->assertFalse($sinListado['ok']);

        $c = $this->comprobante();
        $r = $servicio->registrarDesdeXml($this->empresa->id, '<factura/>', 'credito');

        $this->assertTrue($r['ok'], $r['error'] ?? '');
        $compra = Purchase::withoutGlobalScopes()->with('items')->findOrFail($r['id']);
        $this->assertCount(1, $compra->items, 'el detalle sale del XML');
        $this->assertSame(self::CLAVE, $compra->clave_acceso);
        $this->assertNotNull($c->fresh()->conciliado_con, 'se casó con su factura del listado');
    }

    /** El XML que trae el proveedor: 100 al 15 % y 20 a tarifa cero, mismo total que el listado (135). */
    private function xmlConDetalle(array $pagos = []): void
    {
        $this->mock(ServicioSri::class, fn ($m) => $m->shouldReceive('leerXmlComprobante')->andReturn([
            'ok' => true, 'estado' => 'SIN_AUTORIZACION_ADJUNTA',
            'comprobante' => [
                'tipo' => 'factura', 'clave_acceso' => self::CLAVE, 'numero' => '001-001-000000123',
                'fecha_emision' => '10/09/2026', 'importe_total' => '135.00',
                'emisor' => ['ruc' => '1790012345001', 'razon_social' => 'Insumos del Valle S.A.'],
                'comprador' => ['identificacion' => '1793229485001'],
                'detalles' => [
                    ['codigo' => 'BOT-750', 'descripcion' => 'Botella 750 ml', 'cantidad' => '100', 'precio_unitario' => '1.00',
                     'subtotal' => '100.00', 'impuestos' => [['codigo' => '2', 'codigo_porcentaje' => '4', 'base_imponible' => '100.00', 'valor' => '15.00']]],
                    ['codigo' => 'TAP-01', 'descripcion' => 'Tapa sintética', 'cantidad' => '50', 'precio_unitario' => '0.40',
                     'subtotal' => '20.00', 'impuestos' => [['codigo' => '2', 'codigo_porcentaje' => '0', 'base_imponible' => '20.00', 'valor' => '0.00']]],
                ],
                'impuestos' => [], 'pagos' => $pagos,
            ],
        ]));
    }

    public function test_una_compra_registrada_a_mano_se_completa_con_el_detalle_del_xml(): void
    {
        $inventario = $this->cuenta('1.1.03.01', 'Inventario de insumos', 'activo');
        $iva = AccountPlan::withoutGlobalScopes()->where('empresa_id', $this->empresa->id)->where('code', '1.1.02.05')->value('id');
        foreach ([['insumo', 'compra_contado', $inventario->id], ['global', 'iva_compras', $iva]] as [$tipo, $mov, $cuenta]) {
            \App\Models\AccountingMap::withoutGlobalScopes()->create([
                'empresa_id' => $this->empresa->id, 'tipo_item' => $tipo, 'tipo_movimiento' => $mov, 'account_plan_id' => $cuenta,
            ]);
        }
        $caja = CashRegister::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'nombre' => 'Caja', 'tipo' => 'principal', 'saldo_actual' => 0, 'activo' => true,
        ]);
        $item = \App\Models\InventoryItem::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'codigo' => 'ENV-' . uniqid(), 'nombre' => 'Envases',
            'type' => 'insumo', 'account_plan_id' => $inventario->id, 'purchase_price' => 1, 'activo' => true,
        ]);

        // Como pasó con las facturas viejas: registrada a mano, una línea, cantidad 1.
        $this->mock(ServicioSri::class, fn ($m) => $m->shouldReceive('autorizacionComprobante')
            ->andReturn(['ok' => false, 'estado' => 'NO_ENCONTRADO']));
        $c = $this->comprobante();
        $servicio = app(ClasificadorComprobantesService::class);
        $manual = $servicio->clasificar($c, ['inventory_item_id' => $item->id, 'cantidad' => 1, 'pago' => "efectivo:{$caja->id}"]);
        $this->assertTrue($manual['ok'], $manual['error'] ?? '');
        $this->assertEquals(1, (float) $item->fresh()->stock_actual);

        // Llega su XML: se completa con el detalle real.
        $this->xmlConDetalle();
        $r = app(ClasificadorComprobantesService::class)->registrarDesdeXml($this->empresa->id, '<factura/>', null);
        $this->assertTrue($r['ok'], $r['error'] ?? '');

        $compra = Purchase::withoutGlobalScopes()->with('items')->findOrFail($manual['id']);
        $this->assertCount(2, $compra->items, 'las líneas reales de la factura');
        $this->assertEquals(135, (float) $compra->total, 'el total no cambia');
        $this->assertNotNull($compra->journal_entry_id);
        $this->assertNotNull($compra->xml_path, 'el XML queda de respaldo');
        $this->assertEquals(150, (float) $item->fresh()->stock_actual, 'el kardex queda con las cantidades reales: 100 + 50');
        $this->assertEquals(-135, (float) $caja->fresh()->saldo_actual, 'la caja no se toca: un solo pago');
    }

    public function test_un_gasto_registrado_a_mano_se_completa_con_el_detalle_del_xml(): void
    {
        $tipo = TipoGasto::create([
            'nombre' => 'Suministros', 'account_plan_id' => $this->cuenta('6.1.01.03', 'Suministros', 'gasto')->id,
            'deducible' => true, 'activo' => true,
        ]);
        $this->mock(ServicioSri::class, fn ($m) => $m->shouldReceive('autorizacionComprobante')
            ->andReturn(['ok' => false, 'estado' => 'NO_ENCONTRADO']));
        $c = $this->comprobante();
        $manual = app(ClasificadorComprobantesService::class)->clasificar($c, ['destino' => 'gasto', 'tipo_gasto_id' => $tipo->id, 'pago' => 'credito']);
        $this->assertTrue($manual['ok'], $manual['error'] ?? '');

        $this->xmlConDetalle();
        $r = app(ClasificadorComprobantesService::class)->registrarDesdeXml($this->empresa->id, '<factura/>', null);
        $this->assertTrue($r['ok'], $r['error'] ?? '');

        $gasto = Gasto::with('lineas')->findOrFail($manual['id']);
        $this->assertCount(2, $gasto->lineas);
        $this->assertSame('Botella 750 ml', $gasto->lineas->firstWhere('iva', '>', 0)->descripcion);
        $this->assertEquals(135, (float) $gasto->total);
        $this->assertSame(self::CLAVE, $gasto->autorizacion);
    }

    public function test_en_un_solo_bloque_se_dice_que_es_el_producto_nuevo_y_se_registra(): void
    {
        $usuario = \App\Models\User::factory()->create(['empresa_id' => $this->empresa->id]);
        $this->actingAs($usuario);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('contabilidad'));
        \Filament\Facades\Filament::setTenant($this->empresa);

        \App\Models\AccountingMap::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'tipo_item' => 'global', 'tipo_movimiento' => 'iva_compras',
            'account_plan_id' => AccountPlan::withoutGlobalScopes()->where('empresa_id', $this->empresa->id)
                ->where('code', '1.1.02.05')->value('id'),
        ]);
        \App\Models\AccountingMap::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'tipo_item' => 'global', 'tipo_movimiento' => 'compra_credito_local',
            'account_plan_id' => AccountPlan::withoutGlobalScopes()->where('empresa_id', $this->empresa->id)
                ->where('code', '2.1.01.01')->value('id'),
        ]);
        $tipo = TipoGasto::create([
            'nombre' => 'Limpieza bloque', 'account_plan_id' => $this->cuenta('6.1.01.09', 'Limpieza', 'gasto')->id,
            'deducible' => true, 'activo' => true,
        ]);

        // Dos cajas: el XML dice "efectivo" (01) pero no de cuál; con plazo de 30 días que no es crédito.
        $chica = CashRegister::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'nombre' => 'Caja chica', 'tipo' => 'chica', 'saldo_actual' => 0, 'activo' => true,
        ]);
        CashRegister::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'nombre' => 'Cobros', 'tipo' => 'principal', 'saldo_actual' => 0, 'activo' => true,
        ]);
        \App\Models\AccountingMap::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'tipo_item' => 'insumo', 'tipo_movimiento' => 'compra_contado',
            'account_plan_id' => $this->cuenta('1.1.03.03', 'Inventario insumos contado', 'activo')->id,
        ]);

        // Sube el XML: nada se registra, la factura queda para auditar.
        $this->xmlConDetalle([['codigo' => '01', 'total' => '135.00', 'plazo' => '30', 'unidad_tiempo' => 'dias']]);
        $this->comprobante();
        $r = app(ClasificadorComprobantesService::class)->registrarDesdeXml($this->empresa->id, '<factura/>', null);
        $this->assertSame('pendiente', $r['estado'] ?? null, $r['error'] ?? '');
        $this->assertTrue((bool) Purchase::withoutGlobalScopes()->find($r['id'])->requiere_forma_pago, 'espera su auditoría');

        // En el mismo bloque: se ve qué dice el XML y qué es cada producto nuevo.
        $componente = \Livewire\Livewire::test(\App\Livewire\CompletarFacturas::class)
            ->assertSee('Facturas por auditar')
            ->assertSee('sin sistema financiero (efectivo)')
            ->assertSee('Botella 750 ml');
        // El ERP ya propone: la botella es un bien sin ítem parecido, se propone crearla.
        $botella = ProductoProveedor::withoutGlobalScopes()->where('empresa_id', $this->empresa->id)
            ->where('descripcion', 'Botella 750 ml')->firstOrFail();
        $this->assertSame('nuevo:insumo', $componente->get("producto.{$botella->id}"));

        // Se acepta la propuesta de la botella (crea el insumo) y la tapa se manda a un gasto.
        foreach (ProductoProveedor::withoutGlobalScopes()->where('empresa_id', $this->empresa->id)->where('id', '!=', $botella->id)->pluck('id') as $id) {
            $componente->set("producto.{$id}", "gasto:{$tipo->id}");
        }
        \App\Models\AccountingMap::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'tipo_item' => 'insumo', 'tipo_movimiento' => 'compra_credito_local',
            'account_plan_id' => $this->cuenta('1.1.03.02', 'Inventario de insumos', 'activo')->id,
        ]);
        // Sin decir de qué caja salió el efectivo, no se registra ni se supone crédito.
        $componente->call('registrar', $r['id']);
        $this->assertSame('borrador', Purchase::withoutGlobalScopes()->find($r['id'])->status);

        $componente->set('cuentaPor.efectivo', "efectivo:{$chica->id}")->call('registrarTodas');
        $this->assertTrue(InventoryItem::withoutGlobalScopes()->where('empresa_id', $this->empresa->id)
            ->where('nombre', 'Botella 750 ml')->where('type', 'insumo')->exists(), 'el insumo se creó en el mismo bloque');
        $this->assertSame([], $componente->errors()->all(), implode(' ', $componente->errors()->all()));

        $compra = Purchase::withoutGlobalScopes()->find($r['id']);
        $this->assertSame('confirmado', $compra->status);
        $this->assertSame('efectivo', $compra->forma_pago, 'manda el XML: efectivo, no crédito');
        $this->assertSame($chica->id, $compra->cash_register_id);
    }

    public function test_una_compra_personal_respeta_lo_declarado_y_despues_queda_fuera(): void
    {
        $tipo = TipoGasto::create([
            'nombre' => 'Útiles', 'account_plan_id' => $this->cuenta('6.1.08.01', 'Suministros', 'gasto')->id,
            'deducible' => true, 'activo' => true,
        ]);
        $servicio = app(ClasificadorComprobantesService::class);

        // Mes ya declarado (cargado del portal): entra como no deducible, con su IVA como se declaró.
        \App\Models\Declaracion::create(['empresa_id' => $this->empresa->id, 'seguimiento' => (string) \Illuminate\Support\Str::uuid(),
            'tipo' => 'f104', 'anio' => 2026, 'mes' => 9, 'estado' => 'listo', 'origen' => 'cargada']);
        $declarado = $this->comprobante();
        $r = $servicio->clasificar($declarado, ['destino' => 'personal', 'tipo_gasto_id' => $tipo->id, 'pago' => 'credito']);
        $this->assertTrue($r['ok'], $r['error'] ?? '');
        $gasto = \App\Models\Gasto::with('lineas')->findOrFail($r['id']);
        $this->assertGreaterThan(0, (float) $gasto->no_deducible);
        $this->assertGreaterThan(0, (float) $gasto->iva, 'el IVA queda como se declaró');
        $this->assertStringContainsString('ya la declaró', $gasto->lineas->first()->motivo_no_deducible);

        // Mes sin declarar: fuera de la contabilidad, del 104 y del ATS.
        $nuevo = $this->comprobante('1010202601179001234500120010010000009991234567811', '001-001-000000999');
        $nuevo->update(['fecha_emision' => '2026-10-10']);
        $r = $servicio->clasificar($nuevo->fresh(), ['destino' => 'personal', 'tipo_gasto_id' => $tipo->id]);
        $this->assertTrue($r['ok'], $r['error'] ?? '');
        $this->assertSame('personal', $nuevo->fresh()->conciliado_con);
        $this->assertSame(0, \App\Models\Gasto::where('numero_documento', '001-001-000000999')->count());
    }
}
