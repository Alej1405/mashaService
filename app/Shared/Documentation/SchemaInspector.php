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
                'is_tenant'   => collect($columns)->contains(fn ($c) => $c['name'] === 'empresa_id'),
                'columns'     => $columns,
                'constraints' => $this->constraints($table),
                'indexes'     => $this->indexes($table),
            ];
        }, $tables);

        return ['tables' => $detail, 'foreignKeys' => $fks];
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
