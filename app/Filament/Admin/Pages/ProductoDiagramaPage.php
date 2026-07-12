<?php

namespace App\Filament\Admin\Pages;

use App\Shared\Documentation\ModuleDocScanner;
use App\Shared\Documentation\SchemaInspector;
use Filament\Pages\Page;

/**
 * "Módulo Producto — diagrama y diagnóstico". Documentación viva en /admin:
 * el diseño del diagrama es fijo, pero las tablas/columnas (Estado actual) y las
 * operaciones documentadas se leen EN TIEMPO REAL del esquema PostgreSQL y del
 * atributo #[Documentado]. Solo super_admin.
 */
class ProductoDiagramaPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-cube-transparent';
    protected static ?string $navigationGroup = 'Plataforma';
    protected static ?string $navigationLabel = 'Módulo Producto — diagrama';
    protected static ?string $title           = 'Módulo Producto — diagrama y diagnóstico';
    protected static ?int    $navigationSort  = 3;

    protected static string $view = 'filament.admin.pages.producto-diagrama';

    /** Tablas del contexto Producto que se documentan en "Estado actual". */
    private const TABLAS = [
        'store_products'      => ['tag' => 'núcleo', 'nota' => null],
        'store_categories'    => ['tag' => 'categoría', 'nota' => 'Relación sana: store_products.store_category_id → store_categories.id'],
        'store_product_stock' => ['tag' => 'pivote inventario', 'nota' => 'Puente producto ↔ inventory_items. El stock se LEE de aquí, no se guarda en el producto.'],
        'product_designs'     => ['tag' => 'solo Producción', 'nota' => 'Ya no la usa el flujo de producto. Queda para el refactor de Producción (ahí se dropea).'],
    ];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    /**
     * Estado actual: por cada tabla existente, sus columnas reales clasificadas por
     * contexto (general / precio / media / inventario / muerta) — en tiempo real.
     *
     * @return list<array{table:string,tag:string,nota:?string,count:int,columns:list<array{name:string,ctx:string,note:?string}>}>
     */
    public function getTablesData(): array
    {
        $inspector = app(SchemaInspector::class);

        // Set de columnas con FK real, para detectar *_id huérfanas (muertas).
        $conFk = [];
        foreach ($inspector->foreignKeys() as $fk) {
            $conFk[$fk['from_table'] . '.' . $fk['from_column']] = $fk['to_table'];
        }

        $salida = [];
        foreach (self::TABLAS as $tabla => $meta) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($tabla)) {
                continue;
            }

            $columnas = array_map(function (array $col) use ($tabla, $conFk) {
                return [
                    'name' => $col['name'],
                    'ctx'  => $this->clasificar($col['name'], $tabla, $conFk),
                    'note' => $this->notaColumna($col['name'], $tabla, $conFk),
                ];
            }, $inspector->columns($tabla));

            $salida[] = [
                'table'   => $tabla,
                'tag'     => $meta['tag'],
                'nota'    => $meta['nota'],
                'count'   => count($columnas),
                'columns' => $columnas,
            ];
        }

        return $salida;
    }

    /**
     * Operaciones de negocio documentadas (#[Documentado]) en tiempo real,
     * aplanadas y agrupadas por su 'grupo'.
     *
     * @return array<string, list<array{clase:string,descripcion:string,tipo:string,archivo:string}>>
     */
    public function getOperacionesData(): array
    {
        return app(ModuleDocScanner::class)->scanClasses();
    }

    /** Clasifica una columna por contexto de negocio (heurística por nombre + FK). */
    private function clasificar(string $col, string $tabla, array $conFk): string
    {
        if ($this->esMuerta($col, $tabla, $conFk)) {
            return 'dead';
        }
        if (str_contains($col, 'precio') || str_contains($col, 'cantidad_minima') || $col === 'unidad_precio') {
            return 'price';
        }
        if (str_contains($col, 'imagen') || str_contains($col, 'galeria') || str_contains($col, 'meta_')
            || $col === 'descripcion' || $col === 'caracteristicas') {
            return 'media';
        }
        if (str_contains($col, 'stock') || $col === 'sku' || str_contains($col, 'inventory')) {
            return 'inv';
        }

        return 'genr';
    }

    /** *_id (que no sea id/empresa_id) sin FK real = referencia huérfana (muerta). */
    private function esMuerta(string $col, string $tabla, array $conFk): bool
    {
        return str_ends_with($col, '_id')
            && $col !== 'id'
            && $col !== 'empresa_id'
            && ! isset($conFk[$tabla . '.' . $col]);
    }

    private function notaColumna(string $col, string $tabla, array $conFk): ?string
    {
        if ($this->esMuerta($col, $tabla, $conFk)) {
            return '✗ FK muerta';
        }
        if (isset($conFk[$tabla . '.' . $col])) {
            return '→ ' . $conFk[$tabla . '.' . $col];
        }

        return null;
    }
}
