<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\InventoryItem;
use App\Models\MeasurementUnit;
use App\Models\ProductDesign;
use App\Models\ProductFormulaLine;
use App\Models\ProductPresentation;
use App\Models\ProductProductionStep;
use App\Models\StoreCategory;
use App\Models\StoreCoupon;
use App\Models\StoreCustomer;
use App\Models\StoreProduct;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EcommerceTraceabilitySeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Empresa demo ──────────────────────────────────────────────
        $empresa = Empresa::withoutGlobalScopes()->firstOrCreate(
            ['slug' => 'demo-empresa'],
            [
                'name'   => 'Empresa Demo',
                'email'  => 'demo@mashaec.net',
                'plan'   => 'enterprise',
                'activo' => true,
            ]
        );

        // ── 2. Usuario admin de la empresa ───────────────────────────────
        $user = User::firstOrCreate(
            ['email' => 'admin@demo.net'],
            [
                'name'       => 'Admin Demo',
                'password'   => Hash::make('password'),
                'empresa_id' => $empresa->id,
            ]
        );
        if (!$user->hasRole('admin_empresa')) {
            $user->assignRole('admin_empresa');
        }

        // ── 3. Unidades de medida ────────────────────────────────────────
        $litro  = $this->unit($empresa->id, 'Litro',         'L');
        $ml     = $this->unit($empresa->id, 'Mililitro',     'ml');
        $kg     = $this->unit($empresa->id, 'Kilogramo',     'kg');
        $gr     = $this->unit($empresa->id, 'Gramo',         'g');
        $unidad = $this->unit($empresa->id, 'Unidad',        'u');
        $metro  = $this->unit($empresa->id, 'Metro',         'm');
        $talla  = $this->unit($empresa->id, 'Talla',         'talla');

        // ── 4. PUNTO DE ENTRADA 1 — InventoryItem (producto terminado) ───
        //    Se agrega directamente en el módulo de Inventario del ERP.

        // Insumos de producción
        $quimicoA   = $this->item($empresa->id, 'INS-001', 'Químico Base A',    'insumo',          $litro->id,  5.50,  null,  100);
        $fragrancia = $this->item($empresa->id, 'INS-002', 'Fragancia Pino',    'insumo',          $litro->id,  12.00, null,  30);
        $agua       = $this->item($empresa->id, 'INS-003', 'Agua Purificada',   'materia_prima',   $litro->id,  0.50,  null,  500);
        $tela       = $this->item($empresa->id, 'INS-004', 'Tela Algodón 100%', 'materia_prima',   $metro->id,  8.00,  null,  200);
        $hilos      = $this->item($empresa->id, 'INS-005', 'Hilos de Costura',  'insumo',          $unidad->id, 0.30,  null,  1000);

        // Productos terminados (entrada directa desde Inventario)
        $pinteno250 = $this->item($empresa->id, 'PT-001', 'Pinteno 250ml',  'producto_terminado', $ml->id,     1.20, 2.50,  80);
        $pinteno500 = $this->item($empresa->id, 'PT-002', 'Pinteno 500ml',  'producto_terminado', $ml->id,     2.00, 4.50,  50);
        $pinenoGal  = $this->item($empresa->id, 'PT-003', 'Pinteno Galón',  'producto_terminado', $litro->id,  7.00, 15.00, 20);
        $camisaS    = $this->item($empresa->id, 'PT-004', 'Camisa Casual S','producto_terminado', $talla->id,  6.00, 22.00, 30);
        $camisaM    = $this->item($empresa->id, 'PT-005', 'Camisa Casual M','producto_terminado', $talla->id,  6.00, 22.00, 25);
        $camisaL    = $this->item($empresa->id, 'PT-006', 'Camisa Casual L','producto_terminado', $talla->id,  6.00, 22.00, 15);

        // ── 5. PUNTO DE ENTRADA 2 — Diseño de Productos ─────────────────
        //    Se configura en el panel Enterprise → Diseño de Producto.

        // Producto 1: Pinteno (líquido)
        $disenoPinteno = ProductDesign::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $empresa->id, 'nombre' => 'Pinteno'],
            [
                'categoria'        => 'Limpieza',
                'propuesta_valor'  => '<p><strong>Pinteno</strong> es un desengrasante multiusos de alta eficacia, formulado con agentes activos de origen natural. Elimina grasa, mugre y olores en superficies de cocina, baño e industria.</p><ul><li>Biodegradable</li><li>Sin fosfatos</li><li>Aroma fresco a pino</li></ul>',
                'notas_estrategicas' => 'Línea insignia de limpieza. Precio competitivo frente a marcas importadas. Target: amas de casa y PYMES de limpieza.',
                'activo'           => true,
            ]
        );

        $this->productionSteps($disenoPinteno->id, [
            ['orden' => 1, 'nombre' => 'Preparación de insumos',  'descripcion' => 'Verificar stock y medir insumos según fórmula.', 'tiempo_estimado_minutos' => 15],
            ['orden' => 2, 'nombre' => 'Mezclado',                'descripcion' => 'Mezclar Químico Base A con agua a temperatura ambiente. Agitar 10 min.', 'tiempo_estimado_minutos' => 20],
            ['orden' => 3, 'nombre' => 'Añadir fragancia',        'descripcion' => 'Incorporar fragancia lentamente sin parar de agitar.', 'tiempo_estimado_minutos' => 5],
            ['orden' => 4, 'nombre' => 'Control de calidad',      'descripcion' => 'Verificar pH (6.5–7.5) y densidad.', 'tiempo_estimado_minutos' => 10],
            ['orden' => 5, 'nombre' => 'Envasado y etiquetado',   'descripcion' => 'Llenar envases, cerrar y colocar etiqueta según presentación.', 'tiempo_estimado_minutos' => 30],
        ]);

        // Presentaciones con su fórmula individual
        $presP250 = $this->presentation($disenoPinteno->id, 'Pinteno 250ml', $ml->id, 1.20);
        $this->formulaLines($presP250->id, [
            ['inventory_item_id' => $quimicoA->id,   'cantidad' => 0.150, 'measurement_unit_id' => $litro->id],
            ['inventory_item_id' => $fragrancia->id, 'cantidad' => 0.025, 'measurement_unit_id' => $litro->id],
            ['inventory_item_id' => $agua->id,       'cantidad' => 0.075, 'measurement_unit_id' => $litro->id],
        ]);

        $presP500 = $this->presentation($disenoPinteno->id, 'Pinteno 500ml', $ml->id, 2.00);
        $this->formulaLines($presP500->id, [
            ['inventory_item_id' => $quimicoA->id,   'cantidad' => 0.300, 'measurement_unit_id' => $litro->id],
            ['inventory_item_id' => $fragrancia->id, 'cantidad' => 0.050, 'measurement_unit_id' => $litro->id],
            ['inventory_item_id' => $agua->id,       'cantidad' => 0.150, 'measurement_unit_id' => $litro->id],
        ]);

        $presGalon = $this->presentation($disenoPinteno->id, 'Pinteno Galón', $litro->id, 7.00);
        $this->formulaLines($presGalon->id, [
            ['inventory_item_id' => $quimicoA->id,   'cantidad' => 1.200, 'measurement_unit_id' => $litro->id],
            ['inventory_item_id' => $fragrancia->id, 'cantidad' => 0.200, 'measurement_unit_id' => $litro->id],
            ['inventory_item_id' => $agua->id,       'cantidad' => 0.600, 'measurement_unit_id' => $litro->id],
        ]);

        // Producto 2: Camisa Casual (textil)
        $disenoCamisa = ProductDesign::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $empresa->id, 'nombre' => 'Camisa Casual'],
            [
                'categoria'        => 'Textil',
                'propuesta_valor'  => '<p><strong>Camisa Casual</strong> confeccionada en algodón 100% de primera calidad. Corte moderno, transpirable y de larga duración.</p>',
                'notas_estrategicas' => 'Línea de ropa casual. Diferenciación por calidad de tela vs. importaciones chinas.',
                'activo'           => true,
            ]
        );

        $this->productionSteps($disenoCamisa->id, [
            ['orden' => 1, 'nombre' => 'Corte de tela',      'descripcion' => 'Cortar tela según patrón de cada talla.', 'tiempo_estimado_minutos' => 20],
            ['orden' => 2, 'nombre' => 'Costura principal',  'descripcion' => 'Unir piezas principales (cuerpo, mangas).', 'tiempo_estimado_minutos' => 45],
            ['orden' => 3, 'nombre' => 'Acabados',           'descripcion' => 'Ojales, botones y remates.', 'tiempo_estimado_minutos' => 20],
            ['orden' => 4, 'nombre' => 'Control de calidad', 'descripcion' => 'Revisión de costuras y acabados.', 'tiempo_estimado_minutos' => 10],
            ['orden' => 5, 'nombre' => 'Planchado y empaque','descripcion' => 'Planchar, doblar y empacar con etiqueta.', 'tiempo_estimado_minutos' => 15],
        ]);

        foreach ([
            ['Camisa Casual Talla S', $talla->id, 6.00, $camisaS],
            ['Camisa Casual Talla M', $talla->id, 6.00, $camisaM],
            ['Camisa Casual Talla L', $talla->id, 6.00, $camisaL],
        ] as [$nombre, $unitId, $costo, $item]) {
            $pres = $this->presentation($disenoCamisa->id, $nombre, $unitId, $costo);
            $this->formulaLines($pres->id, [
                ['inventory_item_id' => $tela->id,  'cantidad' => 1.5, 'measurement_unit_id' => $metro->id],
                ['inventory_item_id' => $hilos->id, 'cantidad' => 3,   'measurement_unit_id' => $unidad->id],
            ]);
        }

        // ── 6. PUNTO DE ENTRADA 3 — Tienda (StoreProduct) ───────────────
        //    Se administra desde panel Enterprise → E-Commerce → Productos.

        $catLimpieza = StoreCategory::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $empresa->id, 'slug' => 'limpieza'],
            ['nombre' => 'Limpieza del Hogar', 'publicado' => true, 'orden' => 1]
        );
        $catRopa = StoreCategory::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $empresa->id, 'slug' => 'ropa'],
            ['nombre' => 'Ropa y Moda', 'publicado' => true, 'orden' => 2]
        );

        // Productos de tienda vinculados a productos terminados
        $this->storeProduct($empresa->id, $pinteno250->id, $catLimpieza->id, 'Pinteno 250ml — Desengrasante',  'pinteno-250ml',  2.50, true,  false);
        $this->storeProduct($empresa->id, $pinteno500->id, $catLimpieza->id, 'Pinteno 500ml — Desengrasante',  'pinteno-500ml',  4.50, true,  true);
        $this->storeProduct($empresa->id, $pinenoGal->id,  $catLimpieza->id, 'Pinteno Galón — Desengrasante',  'pinteno-galon',  15.00, true, false);
        $this->storeProduct($empresa->id, $camisaS->id,    $catRopa->id,     'Camisa Casual — Talla S',        'camisa-casual-s', 22.00, true, true);
        $this->storeProduct($empresa->id, $camisaM->id,    $catRopa->id,     'Camisa Casual — Talla M',        'camisa-casual-m', 22.00, true, false);
        $this->storeProduct($empresa->id, $camisaL->id,    $catRopa->id,     'Camisa Casual — Talla L',        'camisa-casual-l', 22.00, true, false);

        // ── 7. Cupón de descuento ────────────────────────────────────────
        StoreCoupon::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $empresa->id, 'codigo' => 'BIENVENIDA10'],
            ['tipo' => 'porcentaje', 'valor' => 10, 'activo' => true,
             'minimo_compra' => 10.00, 'maximo_usos' => 100]
        );
        StoreCoupon::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $empresa->id, 'codigo' => 'DESCUENTO5'],
            ['tipo' => 'monto_fijo', 'valor' => 5.00, 'activo' => true,
             'minimo_compra' => 20.00]
        );

        // ── 8. Cliente de prueba (para testear API) ──────────────────────
        StoreCustomer::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $empresa->id, 'email' => 'cliente@test.com'],
            [
                'nombre'   => 'Cliente',
                'apellido' => 'De Prueba',
                'telefono' => '0999123456',
                'password' => Hash::make('password'),
                'activo'   => true,
            ]
        );

        $this->command->info('✓ EcommerceTraceabilitySeeder ejecutado.');
        $this->command->info("  Empresa: {$empresa->name} (slug: {$empresa->slug})");
        $this->command->info('  Usuarios: admin@demo.net / cliente@test.com (pass: password)');
        $this->command->info('  Cupones:  BIENVENIDA10 (10%), DESCUENTO5 ($5 off)');
        $this->command->info('  Tienda:   GET /api/store/demo-empresa/products');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function unit(int $empresaId, string $nombre, string $abrev): MeasurementUnit
    {
        return MeasurementUnit::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $empresaId, 'nombre' => $nombre],
            ['abreviatura' => $abrev, 'activo' => true]
        );
    }

    private function item(
        int $empresaId, string $codigo, string $nombre,
        string $type, int $unitId,
        float $purchasePrice, ?float $salePrice, float $stock
    ): InventoryItem {
        return InventoryItem::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $empresaId, 'codigo' => $codigo],
            [
                'nombre'              => $nombre,
                'type'                => $type,
                'measurement_unit_id' => $unitId,
                'purchase_price'      => $purchasePrice,
                'sale_price'          => $salePrice,
                'stock_actual'        => $stock,
                'stock_minimo'        => 5,
                'activo'              => true,
            ]
        );
    }

    private function presentation(int $designId, string $nombre, int $unitId, float $costo): ProductPresentation
    {
        return ProductPresentation::firstOrCreate(
            ['product_design_id' => $designId, 'nombre' => $nombre],
            ['measurement_unit_id' => $unitId, 'costo_estimado' => $costo, 'activa' => true]
        );
    }

    private function formulaLines(int $presentationId, array $lines): void
    {
        // Solo crear si la presentación no tiene fórmula aún
        if (ProductFormulaLine::where('presentation_id', $presentationId)->exists()) {
            return;
        }
        foreach ($lines as $line) {
            ProductFormulaLine::create([
                'presentation_id'             => $presentationId,
                'inventory_item_id'           => $line['inventory_item_id'],
                'cantidad'                    => $line['cantidad'],
                'measurement_unit_id'         => $line['measurement_unit_id'],
                'es_subproducto_manufacturado' => false,
            ]);
        }
    }

    private function productionSteps(int $designId, array $steps): void
    {
        if (ProductProductionStep::where('product_design_id', $designId)->exists()) {
            return;
        }
        foreach ($steps as $step) {
            ProductProductionStep::create(['product_design_id' => $designId, ...$step]);
        }
    }

    private function storeProduct(
        int $empresaId, int $itemId, int $categoryId,
        string $nombre, string $slug,
        float $precio, bool $publicado, bool $destacado
    ): StoreProduct {
        return StoreProduct::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $empresaId, 'slug' => $slug],
            [
                'inventory_item_id' => $itemId,
                'store_category_id' => $categoryId,
                'nombre'            => $nombre,
                'descripcion'       => "<p>Producto de alta calidad disponible en nuestra tienda.</p>",
                'precio_venta'      => $precio,
                'publicado'         => $publicado,
                'destacado'         => $destacado,
                'orden'             => 0,
            ]
        );
    }
}
