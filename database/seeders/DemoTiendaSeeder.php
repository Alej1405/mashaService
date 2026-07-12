<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\StoreCategory;
use App\Models\StoreProduct;
use App\Models\StoreProductStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Datos de prueba de la Tienda para la empresa Rivet Ecuador (id 1): productos con
 * datos ricos (precios, categorías, características, SKU) para (1) poblar la BDD y
 * (2) alimentar los microservicios que leen productos de la BDD real.
 *
 * Cubre los DOS caminos del stock virtual del nuevo modelo:
 *   - Productos CON stock: enlazados a inventory_items reales vía store_product_stock
 *     (stock_disponible se computa de inventory_items.stock_actual).
 *   - Productos BAJO PEDIDO: sin enlace (gestionar_stock = false).
 *
 * Idempotente: se puede correr varias veces sin duplicar (clave: empresa_id + slug).
 * Ejecutar: php artisan db:seed --class=DemoTiendaSeeder
 */
class DemoTiendaSeeder extends Seeder
{
    private const EMPRESA_ID = 1;

    public function run(): void
    {
        DB::transaction(function () {
            $empresaId = self::EMPRESA_ID;

            // Categorías: reutiliza las existentes de la empresa, crea las que falten.
            $catSalsas  = $this->categoria($empresaId, 'Salsas alimentos');
            $catLicores = $this->categoria($empresaId, 'Licores');
            $catMaquila = $this->categoria($empresaId, 'Servicio maquila');

            // Inventario real de la empresa con existencias, para respaldar el stock.
            $items = InventoryItem::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)
                ->where('stock_actual', '>', 0)
                ->orderBy('id')
                ->get()
                ->values();

            $orden = 0;
            $itemIdx = 0;

            foreach ($this->catalogo($catSalsas->id, $catLicores->id, $catMaquila->id) as $data) {
                $orden += 10;
                $conStock = $data['stock_por_empaque'] !== null;

                // Item de inventario a enlazar (round-robin sobre el inventario real).
                $item = null;
                if ($conStock && $items->isNotEmpty()) {
                    $item = $items[$itemIdx % $items->count()];
                    $itemIdx++;
                }

                $slug = Str::slug($data['nombre']);

                $producto = StoreProduct::withoutGlobalScopes()->updateOrCreate(
                    ['empresa_id' => $empresaId, 'slug' => $slug],
                    [
                        'store_category_id'            => $data['categoria_id'],
                        'nombre'                       => $data['nombre'],
                        'sku'                          => $data['sku'],
                        'descripcion'                  => $data['descripcion'],
                        'precio_venta'                 => $data['precio_venta'],
                        'precio_distribuidor'          => $data['precio_distribuidor'],
                        'cantidad_minima_distribuidor' => $data['cant_min'],
                        'unidad_precio'                => $data['unidad'],
                        'caracteristicas'              => $data['caracteristicas'],
                        'meta_titulo'                  => $data['nombre'] . ' | Rivet Ecuador',
                        'meta_descripcion'             => Str::limit($data['descripcion'], 150),
                        'publicado'                    => true,
                        'destacado'                    => $data['destacado'],
                        'orden'                        => $orden,
                    ]
                );

                if ($item) {
                    StoreProductStock::withoutGlobalScopes()->updateOrCreate(
                        [
                            'empresa_id'        => $empresaId,
                            'store_product_id'  => $producto->id,
                            'inventory_item_id' => $item->id,
                        ],
                        ['cantidad' => $data['stock_por_empaque']],
                    );
                }
            }
        });
    }

    private function categoria(int $empresaId, string $nombre): StoreCategory
    {
        return StoreCategory::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $empresaId, 'slug' => Str::slug($nombre)],
            [
                'nombre'    => $nombre,
                'publicado' => true,
                'orden'     => 0,
            ],
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalogo(int $salsas, int $licores, int $maquila): array
    {
        return [
            // ── Salsas (con stock real) ──
            ['nombre' => 'Salsa de Ají Criollo 250ml', 'categoria_id' => $salsas, 'sku' => 'SAL-AJI-250', 'precio_venta' => 2.50, 'precio_distribuidor' => 1.80, 'cant_min' => 12, 'unidad' => 'unidad', 'destacado' => true,  'stock_por_empaque' => 1,  'descripcion' => 'Salsa artesanal de ají criollo, receta tradicional ecuatoriana.', 'caracteristicas' => ['Presentación' => '250 ml', 'Picor' => 'Medio', 'Vida útil' => '12 meses']],
            ['nombre' => 'Salsa BBQ Ahumada 350ml',    'categoria_id' => $salsas, 'sku' => 'SAL-BBQ-350', 'precio_venta' => 3.20, 'precio_distribuidor' => 2.40, 'cant_min' => 12, 'unidad' => 'unidad', 'destacado' => false, 'stock_por_empaque' => 1,  'descripcion' => 'Salsa barbacoa con notas ahumadas, ideal para carnes y parrilla.', 'caracteristicas' => ['Presentación' => '350 ml', 'Sabor' => 'Ahumado']],
            ['nombre' => 'Kétchup Artesanal 500ml',     'categoria_id' => $salsas, 'sku' => 'SAL-KET-500', 'precio_venta' => 2.80, 'precio_distribuidor' => 2.00, 'cant_min' => 12, 'unidad' => 'unidad', 'destacado' => false, 'stock_por_empaque' => 1,  'descripcion' => 'Kétchup de tomate natural, sin conservantes artificiales.', 'caracteristicas' => ['Presentación' => '500 ml']],
            ['nombre' => 'Mayonesa Casera 400ml',       'categoria_id' => $salsas, 'sku' => 'SAL-MAY-400', 'precio_venta' => 2.60, 'precio_distribuidor' => 1.90, 'cant_min' => 12, 'unidad' => 'unidad', 'destacado' => false, 'stock_por_empaque' => 1,  'descripcion' => 'Mayonesa cremosa estilo casero.', 'caracteristicas' => ['Presentación' => '400 ml']],
            ['nombre' => 'Mostaza Dijon 250ml',         'categoria_id' => $salsas, 'sku' => 'SAL-MOS-250', 'precio_venta' => 2.90, 'precio_distribuidor' => 2.10, 'cant_min' => 12, 'unidad' => 'unidad', 'destacado' => false, 'stock_por_empaque' => 1,  'descripcion' => 'Mostaza estilo Dijon, sabor intenso.', 'caracteristicas' => ['Presentación' => '250 ml', 'Estilo' => 'Dijon']],
            ['nombre' => 'Salsa Picante Habanero 150ml','categoria_id' => $salsas, 'sku' => 'SAL-HAB-150', 'precio_venta' => 3.50, 'precio_distribuidor' => 2.60, 'cant_min' => 24, 'unidad' => 'unidad', 'destacado' => true,  'stock_por_empaque' => 1,  'descripcion' => 'Salsa picante de habanero, para los amantes del picor extremo.', 'caracteristicas' => ['Presentación' => '150 ml', 'Picor' => 'Alto']],

            // ── Licores (con stock real, empaques por caja) ──
            ['nombre' => 'Ron Añejo Reserva 750ml',      'categoria_id' => $licores, 'sku' => 'LIC-RON-750', 'precio_venta' => 18.90, 'precio_distribuidor' => 14.50, 'cant_min' => 6, 'unidad' => 'botella', 'destacado' => true,  'stock_por_empaque' => 6,  'descripcion' => 'Ron añejo de reserva especial, envejecido 5 años en barrica de roble.', 'caracteristicas' => ['Volumen' => '750 ml', 'Grado' => '40°', 'Añejamiento' => '5 años']],
            ['nombre' => 'Aguardiente Premium 700ml',    'categoria_id' => $licores, 'sku' => 'LIC-AGU-700', 'precio_venta' => 9.50,  'precio_distribuidor' => 7.20,  'cant_min' => 6, 'unidad' => 'botella', 'destacado' => false, 'stock_por_empaque' => 6,  'descripcion' => 'Aguardiente anisado premium.', 'caracteristicas' => ['Volumen' => '700 ml', 'Grado' => '29°']],
            ['nombre' => 'Licor de Café 500ml',          'categoria_id' => $licores, 'sku' => 'LIC-CAF-500', 'precio_venta' => 12.00, 'precio_distribuidor' => 9.00,  'cant_min' => 6, 'unidad' => 'botella', 'destacado' => false, 'stock_por_empaque' => 6,  'descripcion' => 'Licor de café artesanal, cuerpo dulce y aromático.', 'caracteristicas' => ['Volumen' => '500 ml']],
            ['nombre' => 'Vodka Cristalino 750ml',       'categoria_id' => $licores, 'sku' => 'LIC-VOD-750', 'precio_venta' => 15.00, 'precio_distribuidor' => 11.50, 'cant_min' => 6, 'unidad' => 'botella', 'destacado' => false, 'stock_por_empaque' => 6,  'descripcion' => 'Vodka de triple destilación, pureza cristalina.', 'caracteristicas' => ['Volumen' => '750 ml', 'Grado' => '40°']],
            ['nombre' => 'Whisky Blend 12 Años 750ml',   'categoria_id' => $licores, 'sku' => 'LIC-WHI-750', 'precio_venta' => 28.00, 'precio_distribuidor' => 22.00, 'cant_min' => 6, 'unidad' => 'botella', 'destacado' => true,  'stock_por_empaque' => 12, 'descripcion' => 'Whisky blended envejecido 12 años.', 'caracteristicas' => ['Volumen' => '750 ml', 'Añejamiento' => '12 años']],

            // ── Servicios de maquila (bajo pedido, sin stock físico) ──
            ['nombre' => 'Maquila de Envasado',    'categoria_id' => $maquila, 'sku' => 'SRV-ENV', 'precio_venta' => 0.35,   'precio_distribuidor' => 0.25,   'cant_min' => 1000, 'unidad' => 'servicio', 'destacado' => false, 'stock_por_empaque' => null, 'descripcion' => 'Servicio de envasado por unidad, pedido mínimo 1000 unidades.', 'caracteristicas' => ['Modalidad' => 'Por unidad', 'Mínimo' => '1000 uds']],
            ['nombre' => 'Maquila de Etiquetado',  'categoria_id' => $maquila, 'sku' => 'SRV-ETI', 'precio_venta' => 0.15,   'precio_distribuidor' => 0.10,   'cant_min' => 1000, 'unidad' => 'servicio', 'destacado' => false, 'stock_por_empaque' => null, 'descripcion' => 'Servicio de etiquetado industrial.', 'caracteristicas' => ['Modalidad' => 'Por unidad']],
            ['nombre' => 'Desarrollo de Fórmula',  'categoria_id' => $maquila, 'sku' => 'SRV-FOR', 'precio_venta' => 450.00, 'precio_distribuidor' => 350.00, 'cant_min' => 1,    'unidad' => 'servicio', 'destacado' => false, 'stock_por_empaque' => null, 'descripcion' => 'Desarrollo de fórmula a medida con ficha técnica entregable.', 'caracteristicas' => ['Entregable' => 'Ficha técnica']],

            // ── Especiales bajo pedido (sin categoría, sin stock) ──
            ['nombre' => 'Combo Degustación Salsas x6', 'categoria_id' => null, 'sku' => 'CMB-SAL-6',  'precio_venta' => 14.00, 'precio_distribuidor' => 10.50, 'cant_min' => 6, 'unidad' => 'combo',  'destacado' => true,  'stock_por_empaque' => null, 'descripcion' => 'Combo de degustación con 6 salsas surtidas.', 'caracteristicas' => ['Incluye' => '6 salsas surtidas']],
            ['nombre' => 'Barril Personalizado 5L',     'categoria_id' => null, 'sku' => 'ESP-BAR-5L', 'precio_venta' => 55.00, 'precio_distribuidor' => 45.00, 'cant_min' => 1, 'unidad' => 'unidad', 'destacado' => false, 'stock_por_empaque' => null, 'descripcion' => 'Barril personalizado de 5 litros con etiqueta a medida.', 'caracteristicas' => ['Capacidad' => '5 L', 'Personalización' => 'Etiqueta']],
        ];
    }
}
