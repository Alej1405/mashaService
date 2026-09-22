<?php

namespace Tests\Feature;

use App\Filament\Operaciones\Pages\RegistrarMovimiento;
use App\Models\Empresa;
use App\Models\InventoryItem;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * La pantalla de movimientos manuales.
 *
 * No repite lo que ya prueba KardexTest: aquí solo importa que el formulario
 * llegue al kardex con los datos correctos y que las reglas duras (una entrada
 * sin costo, una baja sin acta) lleguen al usuario como error y no como
 * movimiento guardado.
 *
 * Corre contra Postgres, no contra el SQLite de phpunit.xml:
 *   DB_CONNECTION=pgsql DB_DATABASE=erp_mashaec_test DB_USERNAME=erp_user \
 *   DB_PASSWORD=… php artisan test --filter=RegistrarMovimientoTest
 */
class RegistrarMovimientoTest extends TestCase
{
    use DatabaseTransactions;

    private Empresa $empresa;
    private InventoryItem $item;
    private \App\Models\Almacen $bodega;

    protected function setUp(): void
    {
        parent::setUp();

        // La base de pruebas no trae los catálogos: el panel y el plan que lo
        // abre se crean aquí, o canAccess devuelve 403 y no hay pantalla.
        $panel = \App\Models\Panel::firstOrCreate(
            ['key' => 'operaciones'],
            ['name' => 'Operaciones', 'path' => 'operaciones', 'activo' => true],
        );
        \App\Models\PanelModule::firstOrCreate(['panel_id' => $panel->id, 'module_key' => 'inventario']);
        $plan = \App\Models\ServicePlan::firstOrCreate(['key' => 'pro'], ['nombre' => 'Plan Pro']);
        $panel->servicePlans()->syncWithoutDetaching([$plan->id]);

        $this->empresa = Empresa::create([
            'name' => 'Rivet pantalla', 'slug' => 'rivet-pantalla-' . uniqid(),
            'email' => 'pantalla@rivet.ec', 'activo' => true,
            // El panel Operaciones solo lo abre el plan pro (tabla plan_panel).
            'plan' => 'pro',
        ]);

        $this->item = InventoryItem::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id,
            'codigo' => 'MP-014', 'nombre' => 'Ácido cítrico', 'type' => 'materia_prima',
            'stock_actual' => 0, 'costo_promedio' => 0, 'saldo_valorado' => 0, 'activo' => true,
        ]);

        $this->bodega = \App\Models\Almacen::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id, 'codigo' => 'BOD-1',
            'nombre' => 'Bodega principal', 'tipo' => 'bodega_propia', 'activo' => true,
        ]);

        $usuario = User::create([
            'name' => 'Bodeguero', 'email' => 'bodega-' . uniqid() . '@rivet.ec',
            'password' => bcrypt('secreto'), 'empresa_id' => $this->empresa->id,
        ]);

        $usuario->empresasAcceso()->attach($this->empresa->id);

        $this->actingAs($usuario);
        Filament::setCurrentPanel(Filament::getPanel('operaciones'));
        Filament::setTenant($this->empresa);

        // Visitar la pantalla registra sus componentes Livewire en el panel;
        // sin esto Livewire::test no encuentra la página.
        $this->get("/operaciones/{$this->empresa->slug}/registrar-movimiento")->assertOk();
    }

    public function test_una_entrada_valorada_llega_al_kardex(): void
    {
        Livewire::test(RegistrarMovimiento::class)
            ->fillForm([
                'tipo' => 'entrada',
                'motivo' => 'compra',
                'item' => $this->item->id,
                'cantidad' => 50,
                'almacen_id' => $this->bodega->id,
                'costo_unitario' => 4.00,
                'documento' => 'COM-2026-0412',
                'fecha' => now()->toDateString(),
            ])
            ->call('registrar');

        $this->item->refresh();

        $this->assertEquals(50, (float) $this->item->stock_actual);
        $this->assertEquals(4.0, (float) $this->item->costo_promedio);
        $this->assertEquals(200.0, (float) $this->item->saldo_valorado);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $this->item->id,
            'motivo'            => 'compra',
            'description'       => 'COM-2026-0412',
            'saldo_cantidad'    => 50,
        ]);
    }

    public function test_la_salida_sale_al_promedio_vigente(): void
    {
        $this->entradaPrevia(10, 4.00);

        Livewire::test(RegistrarMovimiento::class)
            ->fillForm([
                'tipo' => 'salida',
                'motivo' => 'consumo_produccion',
                'item' => $this->item->id,
                'cantidad' => 4,
                'almacen_id' => $this->bodega->id,
                'fecha' => now()->toDateString(),
            ])
            ->call('registrar');

        $this->item->refresh();

        $this->assertEquals(6, (float) $this->item->stock_actual);
        $this->assertEquals(4.0, (float) $this->item->costo_promedio, 'una salida no mueve el promedio');
        $this->assertEquals(24.0, (float) $this->item->saldo_valorado);
    }

    public function test_una_baja_sin_acta_no_se_guarda(): void
    {
        $this->entradaPrevia(10, 4.00);

        Livewire::test(RegistrarMovimiento::class)
            ->fillForm([
                'tipo' => 'salida',
                'motivo' => 'baja',
                'item' => $this->item->id,
                'cantidad' => 2,
                'almacen_id' => $this->bodega->id,
                'fecha' => now()->toDateString(),
            ])
            ->call('registrar');

        $this->item->refresh();

        $this->assertEquals(10, (float) $this->item->stock_actual, 'la baja no debió descontar');
        $this->assertDatabaseMissing('inventory_movements', [
            'inventory_item_id' => $this->item->id,
            'motivo'            => 'baja',
        ]);
    }

    public function test_una_entrada_sin_costo_no_se_guarda(): void
    {
        Livewire::test(RegistrarMovimiento::class)
            ->fillForm([
                'tipo' => 'entrada',
                'motivo' => 'compra',
                'item' => $this->item->id,
                'cantidad' => 5,
                'almacen_id' => $this->bodega->id,
                'costo_unitario' => 0,
                'fecha' => now()->toDateString(),
            ])
            ->call('registrar');

        $this->item->refresh();

        $this->assertEquals(0, (float) $this->item->stock_actual);
    }

    private function entradaPrevia(float $cantidad, float $costo): void
    {
        app(\App\Services\KardexService::class)->registrar([
            'item' => $this->item, 'motivo' => 'compra', 'cantidad' => $cantidad,
            'costo_unitario' => $costo, 'fecha' => now()->toDateString(),
        ]);

        $this->item->refresh();
    }
}
