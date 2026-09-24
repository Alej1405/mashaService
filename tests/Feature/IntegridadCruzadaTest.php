<?php

namespace Tests\Feature;

use App\Models\AccountPlan;
use App\Models\ActivoFijo;
use App\Models\Empresa;
use App\Models\InventoryItem;
use App\Services\ContabilidadService;
use App\Services\Formulario104Service;
use App\Services\GastoService;
use App\Services\MapeoSuperciasService;
use App\Services\SuperciasService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Integridad cruzada entre módulos.
 *
 * Esto no comprueba que cada pieza funcione: comprueba que **después de una
 * operación, todos los módulos dicen lo mismo**. Un ERP donde el kardex, el
 * asiento, el balance y el casillero del 104 no coinciden está roto aunque
 * cada prueba de unidad pase.
 *
 * Marco: skill `contabilidad-ec` — la integridad cruzada es obligatoria.
 *
 * Corre contra Postgres, no contra el SQLite de phpunit.xml:
 *   DB_CONNECTION=pgsql DB_DATABASE=erp_mashaec_test DB_USERNAME=erp_user \
 *   DB_PASSWORD=… php artisan test --filter=IntegridadCruzadaTest
 */
class IntegridadCruzadaTest extends TestCase
{
    use DatabaseTransactions;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::create([
            'name' => 'Rivet integridad', 'slug' => 'rivet-int-' . uniqid(),
            'email' => 'int@rivet.ec', 'activo' => true, 'plan' => 'pro',
            'numero_identificacion' => '1793229485001',
            'inicio_gestion_erp' => '2026-01-01',
        ]);
    }

    private function cuenta(string $code, string $name, string $type, string $nature = 'deudora'): AccountPlan
    {
        return AccountPlan::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'code' => $code, 'name' => $name,
            'type' => $type, 'nature' => $nature, 'level' => 4,
            'accepts_movements' => true, 'is_active' => true,
        ]);
    }

    // ── un dato, un sitio ────────────────────────────────────────────────────

    public function test_no_existen_dos_sitios_para_la_linea_del_estado(): void
    {
        $this->assertFalse(Schema::hasColumn('account_plans', 'linea_estado'),
            'linea_estado y codigo_supercias respondían lo mismo: solo puede quedar una');
    }

    public function test_no_existen_dos_sitios_para_los_activos_fijos(): void
    {
        $this->assertFalse(Schema::hasTable('activos_fijos'),
            'el activo fijo es un ítem de inventario con type=activo_fijo');
    }

    public function test_el_activo_fijo_del_inventario_es_el_mismo_que_ve_contabilidad(): void
    {
        $cuentaActivo = $this->cuenta('1.2.01.05', 'Maquinaria y equipo', 'activo');
        $cuentaGasto  = $this->cuenta('6.2.05.01', 'Gasto por depreciación', 'gasto');

        $item = InventoryItem::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'codigo' => 'MAQ-' . uniqid(),
            'nombre' => 'Marmita de 200 litros', 'type' => 'activo_fijo',
            'account_plan_id' => $cuentaActivo->id, 'purchase_price' => 4800,
            'costo_promedio' => 4800, 'stock_actual' => 1, 'saldo_valorado' => 4800,
            'fecha_compra' => '2026-01-15', 'valor_residual' => 0, 'vida_util_meses' => 120,
            'cuenta_gasto_id' => $cuentaGasto->id, 'activo' => true,
        ]);

        // Contabilidad lo ve sin que nadie lo haya dado de alta dos veces.
        $vistoPorContabilidad = ActivoFijo::withoutGlobalScopes()
            ->where('type', ActivoFijo::TIPO)->where('empresa_id', $this->empresa->id)->get();

        $this->assertCount(1, $vistoPorContabilidad);
        $this->assertSame($item->id, $vistoPorContabilidad->first()->id,
            'inventario y contabilidad miran la misma fila');
        $this->assertEquals(40.0, $vistoPorContabilidad->first()->cuota_mensual);
    }

    public function test_la_depreciacion_del_inventario_llega_al_libro_diario(): void
    {
        $this->cuenta('1.2.01.05', 'Maquinaria y equipo', 'activo');
        $this->cuenta('1.2.09.01', '(-) Depreciación acumulada PPE', 'activo', 'acreedora');
        $cuentaGasto = $this->cuenta('6.2.05.01', 'Gasto por depreciación', 'gasto');

        InventoryItem::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'codigo' => 'MAQ-' . uniqid(),
            'nombre' => 'Marmita de 200 litros', 'type' => 'activo_fijo',
            'purchase_price' => 4800, 'costo_promedio' => 4800, 'stock_actual' => 1,
            'fecha_compra' => '2026-01-15', 'valor_residual' => 0, 'vida_util_meses' => 120,
            'cuenta_gasto_id' => $cuentaGasto->id, 'activo' => true,
        ]);

        $antes = DB::table('journal_entries')->where('empresa_id', $this->empresa->id)->count();

        $r = app(GastoService::class)->depreciarMes($this->empresa->id, 2026, 9);

        $this->assertSame(1, $r['activos'], 'el activo del inventario se deprecia');
        $this->assertEquals(40.0, $r['total']);
        $this->assertGreaterThan($antes,
            DB::table('journal_entries')->where('empresa_id', $this->empresa->id)->count(),
            'una depreciación sin asiento no existe para la contabilidad');
    }

    // ── la cadena completa: operación → kardex → asiento → balance → 104 ─────

    public function test_toda_cuenta_con_movimiento_llega_al_balance_de_supercias(): void
    {
        $caja  = $this->cuenta('1.1.01.01', 'Caja general', 'activo');
        $venta = $this->cuenta('4.1.01.01', 'Ventas de bienes', 'ingreso', 'acreedora');

        // El observer les puso su línea del catálogo al crearlas.
        $this->assertNotNull($caja->fresh()->codigo_supercias);
        $this->assertNotNull($venta->fresh()->codigo_supercias);

        $asiento = DB::table('journal_entries')->insertGetId([
            'empresa_id' => $this->empresa->id, 'fecha' => '2026-03-10',
            'numero' => 'INT-' . uniqid(), 'tipo' => 'venta', 'status' => 'confirmado',
            'descripcion' => 'Venta de prueba', 'total_debe' => 100, 'total_haber' => 100,
            'esta_cuadrado' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ([[$caja->id, 100, 0], [$venta->id, 0, 100]] as [$cuentaId, $debe, $haber]) {
            DB::table('journal_entry_lines')->insert([
                'journal_entry_id' => $asiento, 'account_plan_id' => $cuentaId,
                'debe' => $debe, 'haber' => $haber, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Lo que ve la contabilidad y lo que ve la Superintendencia, del mismo libro.
        $contable = app(ContabilidadService::class)->saldos($this->empresa->id, '2026-12-31', 2026);
        $porCodigo = app(SuperciasService::class)->saldosPorCodigo($this->empresa->id, 2026);

        $this->assertEquals(100, $contable['ingresos']);
        $this->assertNotEmpty($porCodigo, 'si el balance sale vacío es que falta el mapeo');

        $ingresosScvs = array_sum(array_filter(
            $porCodigo,
            fn ($v, $codigo) => str_starts_with((string) $codigo, '4'),
            ARRAY_FILTER_USE_BOTH,
        ));

        $this->assertEquals($contable['ingresos'], round($ingresosScvs, 2),
            'el mismo asiento tiene que dar lo mismo en los dos lenguajes');
    }

    public function test_ninguna_cuenta_con_movimiento_se_queda_fuera_del_balance(): void
    {
        foreach ([
            ['1.1.01.01', 'Caja general', 'activo'],
            ['6.9.99.01', 'Zumbido interplanetario', 'gasto'],
            ['2.1.01.01', 'Proveedores locales', 'pasivo'],
        ] as [$code, $name, $type]) {
            $this->cuenta($code, $name, $type);
        }

        $this->assertSame(0, app(SuperciasService::class)->cuentasSinCodigo($this->empresa->id),
            'una cuenta sin línea desaparece del balance sin avisar');
    }

    public function test_hay_un_solo_contador_de_cuentas_por_confirmar(): void
    {
        $this->cuenta('1.1.01.01', 'Caja general', 'activo');

        // El que respondía distinto ya no existe.
        $this->assertFalse(method_exists(ContabilidadService::class, 'cuentasSinMapear'),
            'dos contadores de lo mismo hacen que las pantallas se contradigan');

        $this->assertIsInt(app(MapeoSuperciasService::class)->porRevisar($this->empresa->id));
    }

    // ── el crédito tributario, que es dinero ─────────────────────────────────

    public function test_un_mes_con_compras_y_sin_ventas_genera_credito_tributario(): void
    {
        $tipo = \App\Models\TipoGasto::create([
            'nombre' => 'Insumos de prueba',
            'account_plan_id' => $this->cuenta('6.1.01.01', 'Gasto de insumos', 'gasto')->id,
            'deducible' => true, 'activo' => true,
        ]);

        $gasto = \App\Models\Gasto::create([
            'empresa_id' => $this->empresa->id, 'fecha' => '2026-04-10',
            'numero_documento' => '001-001-000000099', 'descripcion' => 'Compra sin ventas',
            'estado' => 'confirmado',
        ]);

        \App\Models\GastoLinea::create([
            'gasto_id' => $gasto->id, 'tipo_gasto_id' => $tipo->id,
            'base' => 400, 'porcentaje_iva' => 15, 'iva' => 60, 'deducible' => true,
        ]);

        $r = app(Formulario104Service::class)->calcular($this->empresa->id, 2026, 4);
        $c = $r['casilleros'];

        $this->assertEquals(60, $c['520'], 'el IVA de la compra está');
        $this->assertEquals(1.0, $c['563'], 'sin ventas el factor es 1, no 0');
        $this->assertEquals(60, $c['564'], 'el IVA es crédito tributario íntegro');
        $this->assertEquals(0, $c['565'], 'no se carga nada al gasto');
        $this->assertEquals(60, $c['602'], 'queda como crédito a favor');
        $this->assertEquals(0, $c['999'], 'no hay nada que pagar');
    }

    // ── el mes declarado se congela ──────────────────────────────────────────

    public function test_un_mes_declarado_no_admite_asientos_nuevos(): void
    {
        $declaracion = \App\Models\Declaracion::create([
            'empresa_id' => $this->empresa->id, 'tipo' => 'f104',
            'anio' => 2026, 'mes' => 2, 'estado' => 'listo', 'generado_en' => now(),
            'presentado_en' => now(), 'datos' => ['casilleros' => ['999' => 120.0]],
        ]);

        app(ContabilidadService::class)->cerrarMes(
            $this->empresa->id, 2026, 2, $declaracion->id,
        );

        $this->assertTrue(app(ContabilidadService::class)->mesCerrado($this->empresa->id, 2026, 2));

        // Cualquier módulo que vaya a escribir en ese mes se encuentra el muro.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('está cerrado');

        app(ContabilidadService::class)->exigirEjercicioAbierto($this->empresa->id, '2026-02-15');
    }

    public function test_cerrar_un_mes_no_bloquea_los_demas(): void
    {
        app(ContabilidadService::class)->cerrarMes($this->empresa->id, 2026, 2);

        // Marzo sigue abierto: el cierre es del mes, no del año.
        app(ContabilidadService::class)->exigirEjercicioAbierto($this->empresa->id, '2026-03-15');

        $this->assertFalse(app(ContabilidadService::class)->mesCerrado($this->empresa->id, 2026, 3));
    }

    public function test_la_declaracion_cargada_a_mano_vive_con_las_demas(): void
    {
        // Las de antes del ERP no tienen tabla aparte: son declaraciones.
        $cargada = \App\Models\Declaracion::create([
            'empresa_id' => $this->empresa->id, 'tipo' => 'f104', 'origen' => 'cargada',
            'anio' => 2025, 'mes' => 11, 'estado' => 'listo',
            'generado_en' => '2025-12-10', 'presentado_en' => '2025-12-10',
            'comprobante_presentacion' => '872897621302', 'valor_pagado' => 240.50,
            'datos' => ['casilleros' => ['419' => 8400.0, '999' => 240.50]],
        ]);

        $this->assertTrue($cargada->es_cargada);
        $this->assertSame(1, \App\Models\Declaracion::where('empresa_id', $this->empresa->id)
            ->cargadas()->count());

        // Y alimenta la posición de la empresa igual que una generada.
        $posicion = app(\App\Services\PosicionEmpresaService::class)->resumen($this->empresa->id);

        $this->assertSame(1, $posicion['tributaria']['cargadas']);
        $this->assertGreaterThan(0, $posicion['tributaria']['iva_pagado_12m']);
    }

    public function test_la_posicion_sale_del_libro_diario_y_de_lo_presentado(): void
    {
        $caja  = $this->cuenta('1.1.01.01', 'Caja general', 'activo');
        $deuda = $this->cuenta('2.1.01.01', 'Proveedores locales', 'pasivo', 'acreedora');

        $asiento = DB::table('journal_entries')->insertGetId([
            'empresa_id' => $this->empresa->id, 'fecha' => now()->format('Y-m-d'),
            'numero' => 'POS-' . uniqid(), 'tipo' => 'compra', 'status' => 'confirmado',
            'descripcion' => 'Compra a crédito', 'total_debe' => 1000, 'total_haber' => 1000,
            'esta_cuadrado' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ([[$caja->id, 1000, 0], [$deuda->id, 0, 1000]] as [$cuentaId, $debe, $haber]) {
            DB::table('journal_entry_lines')->insert([
                'journal_entry_id' => $asiento, 'account_plan_id' => $cuentaId,
                'debe' => $debe, 'haber' => $haber, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $posicion = app(\App\Services\PosicionEmpresaService::class)->resumen($this->empresa->id);

        // El mismo asiento que ve la contabilidad es el que lee el banco.
        $this->assertEquals(1000, $posicion['financiera']['activo']);
        $this->assertEquals(1000, $posicion['financiera']['pasivo']);
        $this->assertEquals(100.0, $posicion['financiera']['endeudamiento']);
        $this->assertNotEmpty($posicion['señales']);
    }

    // ── lo cargado da trazabilidad, pero no respalda el cierre ───────────────

    public function test_una_declaracion_cargada_no_cierra_el_mes(): void
    {
        $cargada = \App\Models\Declaracion::create([
            'empresa_id' => $this->empresa->id, 'tipo' => 'f104', 'origen' => 'cargada',
            'anio' => 2026, 'mes' => 7, 'estado' => 'listo',
            'presentado_en' => '2026-08-20', 'comprobante_presentacion' => '872978123571',
            'datos' => ['casilleros' => ['419' => 1000.0, '615' => 250.0, '999' => 0.0]],
        ]);

        $this->assertTrue($cargada->es_cargada);

        // Da trazabilidad: su crédito alimenta la posición de la empresa.
        $posicion = app(\App\Services\PosicionEmpresaService::class)->resumen($this->empresa->id);
        $this->assertSame(1, $posicion['tributaria']['cargadas']);

        // Pero el período sigue abierto: el ERP no tiene los movimientos detrás.
        $this->assertFalse(app(ContabilidadService::class)->mesCerrado($this->empresa->id, 2026, 7),
            'lo cargado del portal no respalda un cierre');

        app(ContabilidadService::class)->exigirEjercicioAbierto($this->empresa->id, '2026-07-15');
    }

    public function test_los_hallazgos_impiden_cerrar_un_periodo_incoherente(): void
    {
        $caja = $this->cuenta('1.1.01.01', 'Caja general', 'activo');

        // Una venta confirmada sin asiento: está en el 104 y no en el balance.
        $cliente = \App\Models\Customer::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'codigo' => 'CF-' . uniqid(),
            'nombre' => 'CONSUMIDOR FINAL', 'tipo_persona' => 'natural',
            'tipo_identificacion' => 'consumidor_final',
            'numero_identificacion' => \App\Services\AtsService::CONSUMIDOR_FINAL,
            'activo' => true, 'publicado' => false,
        ]);

        \App\Models\Sale::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'fecha' => '2026-07-10',
            'customer_id' => $cliente->id, 'referencia' => '001-001-000000777',
            'tipo_venta' => 'contado', 'subtotal' => 100, 'iva' => 15, 'total' => 115,
            'estado' => 'confirmado',   // sin journal_entry_id
        ]);

        $revision = app(\App\Services\AuditoriaPeriodoService::class)
            ->revisar($this->empresa->id, 2026, 7);

        $this->assertFalse($revision['puede_cerrar']);
        $this->assertGreaterThan(0, $revision['bloqueantes']);

        $titulos = implode(' ', array_column($revision['hallazgos'], 'titulo'));
        $this->assertStringContainsString('sin asiento', $titulos);
    }

    public function test_el_credito_tributario_encadena_de_un_mes_al_siguiente(): void
    {
        // Lo que sale de un mes tiene que entrar en el siguiente.
        foreach ([
            [2026, 4, ['615' => 100.0, '617' => 0.0]],
            [2026, 5, ['605' => 100.0, '606' => 0.0, '615' => 160.0, '617' => 0.0]],
            [2026, 6, ['605' => 160.0, '606' => 0.0, '615' => 200.0, '617' => 0.0]],
        ] as [$anio, $mes, $casilleros]) {
            \App\Models\Declaracion::create([
                'empresa_id' => $this->empresa->id, 'tipo' => 'f104', 'origen' => 'cargada',
                'anio' => $anio, 'mes' => $mes, 'estado' => 'listo',
                'presentado_en' => Carbon::create($anio, $mes, 1)->addMonth(),
                'datos' => ['casilleros' => $casilleros],
            ]);
        }

        \Illuminate\Support\Facades\Http::fake([
            '*/declaracion/verificar-cadena' => \Illuminate\Support\Facades\Http::response([
                'periodos' => 3, 'cadena_cuadra' => true, 'saltos' => [],
                'credito_actual' => 200.0, 'ultimo_periodo' => '06/2026',
            ]),
        ]);

        $cadena = app(\App\Services\ImportadorDeclaracionesPdf::class)
            ->verificarCadena($this->empresa->id);

        $this->assertTrue($cadena['cadena_cuadra']);
        $this->assertEquals(200.0, $cadena['credito_actual']);
    }

    // ── del portal a la contabilidad ─────────────────────────────────────────

    /** Un comprobante del portal, como los que deja el .txt del SRI. */
    private function comprobante(string $numero = '001-001-000000500', float $base = 100, float $iva = 15): \App\Models\ComprobanteSri
    {
        return \App\Models\ComprobanteSri::create([
            'empresa_id' => $this->empresa->id, 'clave_acceso' => str_repeat('7', 40) . substr(md5($numero), 0, 9),
            'origen' => 'recibido', 'tipo_comprobante' => '01',
            'identificacion' => '0991331859001', 'razon_social' => 'ATIMASA S.A.',
            'fecha_emision' => '2026-07-15', 'numero' => $numero,
            'base_gravada' => $base, 'base_cero' => 0, 'iva' => $iva, 'total' => $base + $iva,
            'desglose' => 'declarado', 'importado_en' => now(),
        ]);
    }

    public function test_un_comprobante_del_portal_sin_clasificar_no_tiene_asiento(): void
    {
        $c = $this->comprobante();

        $this->assertNull($c->destino);
        $this->assertNull($c->conciliado_con);

        // Suma al 104…
        $r = app(Formulario104Service::class)->calcular($this->empresa->id, 2026, 7);
        $this->assertEquals(100, $r['casilleros']['500']);

        // …y por eso el período no se puede cerrar.
        $revision = app(\App\Services\AuditoriaPeriodoService::class)->revisar($this->empresa->id, 2026, 7);
        $titulos = implode(' ', array_column($revision['hallazgos'], 'titulo'));
        $this->assertStringContainsString('sin registrar en el ERP', $titulos);
    }

    public function test_clasificar_como_gasto_crea_el_gasto_con_su_asiento(): void
    {
        $this->cuenta('6.1.16', 'Gastos de gestión', 'gasto');
        $this->cuenta('1.1.02.05', 'IVA crédito tributario', 'activo');
        $this->cuenta('2.1.01.01', 'Proveedores locales', 'pasivo', 'acreedora');

        $c = $this->comprobante();
        $tipo = \App\Models\TipoGasto::where('nombre', 'Alimentación')->first();

        $this->assertNotNull($tipo->cuentaEn($this->empresa->id),
            'el tipo de gasto resuelve su cuenta en el plan de esta empresa');

        $r = app(\App\Services\ClasificadorComprobantesService::class)
            ->clasificar($c, ['destino' => 'gasto', 'tipo_gasto_id' => $tipo->id], null);

        $this->assertTrue($r['ok'], $r['error'] ?? '');

        $gasto = \App\Models\Gasto::find($r['id']);
        $this->assertSame('confirmado', $gasto->estado);
        $this->assertNotNull($gasto->journal_entry_id, 'un gasto sin asiento no existe para la contabilidad');

        // Y deja de contarse dos veces: ahora está conciliado.
        $c->refresh();
        $this->assertSame('gasto', $c->destino);
        $this->assertSame('gasto', $c->conciliado_con);
        $this->assertSame(0, app(\App\Services\ClasificadorComprobantesService::class)
            ->pendientes($this->empresa->id));
    }

    public function test_clasificar_como_inventario_mueve_el_kardex_y_el_asiento(): void
    {
        $cuentaInv = $this->cuenta('1.1.03.02', 'Inventario de materias primas', 'activo');
        $this->cuenta('2.1.01.01', 'Proveedores locales', 'pasivo', 'acreedora');

        // El asiento de compra sale del mapeo contable de la empresa, que es
        // configuración que toda empresa real tiene.
        $ivaCompras = $this->cuenta('1.1.02.05', 'IVA crédito tributario', 'activo');
        $porPagar = \App\Models\AccountPlan::withoutGlobalScopes()
            ->where('empresa_id', $this->empresa->id)->where('code', '2.1.01.01')->first();

        foreach ([
            ['materia_prima', 'compra_credito_local', $cuentaInv->id],
            ['materia_prima', 'compra_contado', $cuentaInv->id],
            ['global', 'compra_credito_local', $porPagar->id],
            ['global', 'compra_contado', $porPagar->id],
            ['global', 'iva_compras', $ivaCompras->id],
        ] as [$tipoItem, $movimiento, $cuentaId]) {
            DB::table('accounting_maps')->insert([
                'empresa_id' => $this->empresa->id, 'tipo_item' => $tipoItem,
                'tipo_movimiento' => $movimiento, 'account_plan_id' => $cuentaId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $item = \App\Models\InventoryItem::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'codigo' => 'MP-' . uniqid(),
            'nombre' => 'Mortiño', 'type' => 'materia_prima',
            'account_plan_id' => $cuentaInv->id, 'purchase_price' => 0,
            'costo_promedio' => 0, 'stock_actual' => 0, 'saldo_valorado' => 0, 'activo' => true,
        ]);

        $c = $this->comprobante('001-001-000000501', 200, 30);

        // Sin decir qué fue: el ítem ya está clasificado como materia prima en
        // el formulario de bodega, y de ahí sale el destino.
        $r = app(\App\Services\ClasificadorComprobantesService::class)->clasificar($c, [
            'inventory_item_id' => $item->id, 'cantidad' => 4,
        ], null);

        $this->assertTrue($r['ok'] ?? false, $r['error'] ?? 'sin motivo');
        $this->assertSame('inventario', $c->fresh()->destino, 'el destino salió del tipo del ítem');

        $item->refresh();
        $this->assertEquals(4, $item->stock_actual, 'la factura del portal entró al kardex');
        $this->assertEquals(50, $item->costo_promedio, '200 entre 4 unidades');

        $compra = \App\Models\Purchase::withoutGlobalScopes()->find($r['id']);
        $this->assertNotNull($compra->journal_entry_id, 'la compra genera su asiento');
        $this->assertSame('confirmado', $compra->status);
    }

    public function test_lo_que_se_hizo_con_un_proveedor_se_propone_para_sus_otras_facturas(): void
    {
        $this->cuenta('6.1.16', 'Gastos de gestión', 'gasto');
        $this->cuenta('1.1.02.05', 'IVA crédito tributario', 'activo');
        $this->cuenta('2.1.01.01', 'Proveedores locales', 'pasivo', 'acreedora');

        $primera = $this->comprobante('001-001-000000600');
        $segunda = $this->comprobante('001-001-000000601');

        $tipo = \App\Models\TipoGasto::where('nombre', 'Alimentación')->first();
        $servicio = app(\App\Services\ClasificadorComprobantesService::class);

        $servicio->clasificar($primera, ['destino' => 'gasto', 'tipo_gasto_id' => $tipo->id], null);

        // La segunda del mismo proveedor viene con la propuesta puesta.
        $propuesta = $servicio->proponer($segunda);
        $this->assertSame('gasto', $propuesta['destino']);
        $this->assertSame($tipo->id, $propuesta['tipo_gasto_id']);
        $this->assertSame(1, $propuesta['visto']);
    }

    public function test_el_credito_tributario_del_mes_viaja_al_siguiente(): void
    {
        \App\Models\Declaracion::create([
            'empresa_id' => $this->empresa->id, 'tipo' => 'f104',
            'anio' => 2026, 'mes' => 4, 'estado' => 'listo', 'generado_en' => now(),
            'datos' => ['casilleros' => ['615' => 60.0, '617' => 0.0]],
        ]);

        $r = app(Formulario104Service::class)->calcular($this->empresa->id, 2026, 5);

        $this->assertEquals(60, $r['casilleros']['605'],
            'el crédito que no se usó en abril se arrastra a mayo');
    }
}
