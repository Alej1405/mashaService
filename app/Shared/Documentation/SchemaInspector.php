<?php

namespace App\Shared\Documentation;

use Illuminate\Support\Facades\DB;

/**
 * Lee el esquema real de PostgreSQL desde information_schema y los catálogos pg_*.
 * Siempre veraz: el contenido sale de la base misma, no de un texto mantenido aparte.
 *
 * Pensado para alimentar la página "Mapa de la BDD" en /admin (solo super_admin).
 * Tráfico bajo, así que no cachea; si el esquema crece mucho, envolver schema()
 * en Cache::remember() e invalidar tras cada migración.
 */
final class SchemaInspector
{
    public function __construct(
        private readonly string $schema = 'public',
    ) {}

    /**
     * Estructura completa lista para la vista:
     * [
     *   'tables' => [ ['name','is_tenant','columns'=>[...],'constraints'=>[...],'indexes'=>[...]], ... ],
     *   'foreignKeys' => [ ['from_table','from_column','to_table','to_column','constraint'], ... ],
     * ]
     */
    public function schema(): array
    {
        $tables = $this->tables();
        $fks = $this->foreignKeys();

        $detail = array_map(function (string $table) {
            $columns = $this->columns($table);

            return [
                'name'        => $table,
                'module'      => $this->moduleFor($table),
                'is_tenant'   => collect($columns)->contains(fn ($c) => $c['name'] === 'empresa_id'),
                'columns'     => $columns,
                'constraints' => $this->constraints($table),
                'indexes'     => $this->indexes($table),
            ];
        }, $tables);

        return [
            'tables'      => $detail,
            'foreignKeys' => $fks,
            'modules'     => $this->modules($detail),
            'moduleLinks' => $this->moduleLinks($detail, $fks),
        ];
    }

    /**
     * Orden de presentación de los módulos del ERP. El grafo de primer nivel usa
     * exactamente estos, en este orden.
     *
     * @var list<string>
     */
    private const MODULE_ORDER = [
        'Tienda', 'CMS', 'Ventas', 'Compras', 'Inventario', 'Producción',
        'Contabilidad', 'Tesorería', 'Logística', 'Servicios', 'Mailing',
        'Soporte', 'Sistema', 'Otros',
    ];

    /**
     * Clasifica una tabla en su módulo de negocio. Primero excepciones exactas
     * (tablas que no siguen su prefijo), luego reglas por prefijo en orden. Todo
     * lo no reconocido cae en 'Otros' para que ninguna tabla quede fuera del mapa.
     */
    public function moduleFor(string $table): string
    {
        // Excepciones: nombre exacto → módulo (ganan sobre las reglas de prefijo).
        $exact = [
            'product_images'        => 'Tienda',   // imágenes del producto de tienda
            'product_materials'     => 'Tienda',   // materiales del producto de tienda
            'empresa_mailing_stats' => 'Mailing',  // 'empresa_' pero es de Mailing
            'customers'             => 'Ventas',
            'suppliers'             => 'Compras',
            'banks'                 => 'Tesorería',
            'measurement_units'     => 'Inventario',
            'almacenes'             => 'Inventario',
            'ubicaciones_almacen'   => 'Inventario',
            'zonas_almacen'         => 'Inventario',
            'costos_fijos'          => 'Producción',
            'carta_presentaciones'  => 'CMS',
            'account_plans'         => 'Contabilidad',
            'accounting_maps'       => 'Contabilidad',
        ];
        if (isset($exact[$table])) {
            return $exact[$table];
        }

        // Reglas por prefijo, evaluadas en orden (la primera que casa gana).
        $prefixes = [
            'store_'       => 'Tienda',
            'cms_'         => 'CMS',
            'logistics_'   => 'Logística',
            'service_'     => 'Servicios',
            'production_'  => 'Producción',
            'product_'     => 'Producción',
            'inventory_'   => 'Inventario',
            'item_'        => 'Inventario',
            'purchase'     => 'Compras',
            'sale'         => 'Ventas',
            'journal_'     => 'Contabilidad',
            'bank_'        => 'Tesorería',
            'cash_'        => 'Tesorería',
            'credit_card'  => 'Tesorería',
            'debt'         => 'Tesorería',
            'mail'         => 'Mailing',
            'support_'     => 'Soporte',
        ];
        foreach ($prefixes as $prefix => $module) {
            if (str_starts_with($table, $prefix)) {
                return $module;
            }
        }

        // Núcleo de plataforma / acceso / multi-tenant.
        $sistema = [
            'empresas', 'users', 'empresa_user_access', 'panels', 'panel_modules',
            'plan_panel', 'role_module', 'roles', 'permissions',
            'model_has_permissions', 'model_has_roles', 'role_has_permissions',
            'personal_access_tokens', 'system_events', 'telegram_sessions',
        ];
        if (in_array($table, $sistema, true)) {
            return 'Sistema';
        }

        return 'Otros';
    }

    /**
     * Resumen de módulos presentes (nodos del grafo de primer nivel), en el orden
     * canónico y solo los que tienen al menos una tabla.
     *
     * @param  list<array{name:string,module:string,is_tenant:bool}>  $tables
     * @return list<array{name:string,table_count:int,tenant_count:int}>
     */
    private function modules(array $tables): array
    {
        $byModule = collect($tables)->groupBy('module');

        return collect(self::MODULE_ORDER)
            ->filter(fn (string $m) => $byModule->has($m))
            ->map(fn (string $m) => [
                'name'         => $m,
                'table_count'  => $byModule[$m]->count(),
                'tenant_count' => $byModule[$m]->where('is_tenant', true)->count(),
            ])
            ->values()
            ->all();
    }

    /**
     * Aristas entre módulos: cada FK se agrega al par (módulo_origen, módulo_destino).
     * Se descartan los enlaces internos (mismo módulo); el peso es el nº de FKs.
     *
     * @param  list<array{name:string,module:string}>  $tables
     * @param  list<array{from_table:string,to_table:string}>  $fks
     * @return list<array{from_module:string,to_module:string,count:int}>
     */
    private function moduleLinks(array $tables, array $fks): array
    {
        $moduleByTable = collect($tables)->pluck('module', 'name');
        $links = [];

        foreach ($fks as $fk) {
            $from = $moduleByTable[$fk['from_table']] ?? 'Otros';
            $to   = $moduleByTable[$fk['to_table']] ?? 'Otros';
            if ($from === $to) {
                continue;
            }
            $key = $from . '→' . $to;
            $links[$key] = ($links[$key] ?? 0) + 1;
        }

        return collect($links)
            ->map(function (int $count, string $key) {
                [$from, $to] = explode('→', $key);

                return ['from_module' => $from, 'to_module' => $to, 'count' => $count];
            })
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function tables(): array
    {
        $rows = DB::select(
            "SELECT table_name
               FROM information_schema.tables
              WHERE table_schema = ?
                AND table_type = 'BASE TABLE'
              ORDER BY table_name",
            [$this->schema]
        );

        $ignore = ['migrations', 'jobs', 'job_batches', 'failed_jobs', 'cache',
                   'cache_locks', 'sessions', 'password_reset_tokens'];

        return collect($rows)
            ->pluck('table_name')
            ->reject(fn ($t) => in_array($t, $ignore, true))
            ->values()
            ->all();
    }

    /** @return list<array{name:string,type:string,nullable:bool,default:?string,length:?int}> */
    public function columns(string $table): array
    {
        $rows = DB::select(
            "SELECT column_name, data_type, is_nullable, column_default, character_maximum_length
               FROM information_schema.columns
              WHERE table_schema = ? AND table_name = ?
              ORDER BY ordinal_position",
            [$this->schema, $table]
        );

        return array_map(fn ($r) => [
            'name'     => $r->column_name,
            'type'     => $r->data_type,
            'nullable' => $r->is_nullable === 'YES',
            'default'  => $r->column_default,
            'length'   => $r->character_maximum_length,
        ], $rows);
    }

    /**
     * Todas las FKs del esquema (aristas del grafo).
     *
     * @return list<array{from_table:string,from_column:string,to_table:string,to_column:string,constraint:string}>
     */
    public function foreignKeys(): array
    {
        $rows = DB::select(
            "SELECT tc.table_name      AS from_table,
                    kcu.column_name    AS from_column,
                    ccu.table_name     AS to_table,
                    ccu.column_name    AS to_column,
                    tc.constraint_name AS constraint_name
               FROM information_schema.table_constraints tc
               JOIN information_schema.key_column_usage kcu
                 ON tc.constraint_name = kcu.constraint_name
                AND tc.table_schema   = kcu.table_schema
               JOIN information_schema.constraint_column_usage ccu
                 ON ccu.constraint_name = tc.constraint_name
                AND ccu.table_schema    = tc.table_schema
              WHERE tc.constraint_type = 'FOREIGN KEY'
                AND tc.table_schema    = ?",
            [$this->schema]
        );

        return array_map(fn ($r) => [
            'from_table'  => $r->from_table,
            'from_column' => $r->from_column,
            'to_table'    => $r->to_table,
            'to_column'   => $r->to_column,
            'constraint'  => $r->constraint_name,
        ], $rows);
    }

    /**
     * Constraints de una tabla con su definición textual (PK, FK, UNIQUE, CHECK).
     * pg_get_constraintdef() devuelve la definición legible tal cual está en la base
     * — útil para ver tus CHECK y UNIQUE compuestos por empresa_id.
     *
     * @return list<array{name:string,type:string,definition:string}>
     */
    public function constraints(string $table): array
    {
        $qualified = $this->schema . '.' . $table;

        $rows = DB::select(
            "SELECT conname AS name,
                    contype AS type,
                    pg_get_constraintdef(oid) AS definition
               FROM pg_constraint
              WHERE conrelid = ?::regclass
              ORDER BY contype, conname",
            [$qualified]
        );

        $labels = ['p' => 'PRIMARY KEY', 'f' => 'FOREIGN KEY', 'u' => 'UNIQUE', 'c' => 'CHECK', 'x' => 'EXCLUDE'];

        return array_map(fn ($r) => [
            'name'       => $r->name,
            'type'       => $labels[$r->type] ?? $r->type,
            'definition' => $r->definition,
        ], $rows);
    }

    /** @return list<array{name:string,definition:string}> */
    public function indexes(string $table): array
    {
        $rows = DB::select(
            "SELECT indexname AS name, indexdef AS definition
               FROM pg_indexes
              WHERE schemaname = ? AND tablename = ?
              ORDER BY indexname",
            [$this->schema, $table]
        );

        return array_map(fn ($r) => [
            'name'       => $r->name,
            'definition' => $r->definition,
        ], $rows);
    }
}
