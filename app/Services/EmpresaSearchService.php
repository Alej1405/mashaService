<?php

namespace App\Services;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Búsquedas y filtros de empresas — reutilizable en cualquier panel o API.
 *
 * Algoritmos usados:
 *  - pg_trgm GIN index  → buscarPorTexto()         O(log n) búsqueda fuzzy
 *  - JSONB GIN index    → filtrarPorModulo()        O(log n) filtro features
 *  - B-tree index       → buscarPorIdentificacion() O(log n) exacto
 *  - Cursor pagination  → paginarConCursor()        O(1) vs O(n) del OFFSET
 */
class EmpresaSearchService
{
    /**
     * Búsqueda fuzzy por nombre o email usando trigrams de PostgreSQL.
     * Requiere extensión pg_trgm y el índice idx_empresas_name_trgm.
     *
     * Ejemplo: buscarPorTexto('alem') encuentra 'AlemCargo', 'Aleman SA', etc.
     */
    public function buscarPorTexto(string $query): Builder
    {
        $q = trim($query);

        if ($q === '') {
            return Empresa::query();
        }

        // Búsqueda por similitud trigram en nombre Y email
        return Empresa::query()
            ->where(function (Builder $builder) use ($q) {
                $builder->whereRaw("name % ?", [$q])
                    ->orWhereRaw("email ILIKE ?", ["%{$q}%"])
                    ->orWhereRaw("numero_identificacion ILIKE ?", ["{$q}%"]);
            })
            ->orderByRaw("similarity(name, ?) DESC", [$q]);
    }

    /**
     * Filtra empresas por estado de un módulo usando el índice GIN JSONB.
     *
     * @param string $estado  'activo' | 'parcial' | 'inactivo'
     */
    public function filtrarPorModulo(Builder $query, string $modulo, string $estado): Builder
    {
        return match ($estado) {
            'activo'   => $query->whereRaw(
                "features @> ?::jsonb",
                [json_encode([$modulo => ['activo' => true]])]
            ),
            'inactivo' => $query->where(function (Builder $q) use ($modulo) {
                $q->whereRaw("NOT (features @> ?::jsonb)", [json_encode([$modulo => ['activo' => true]])])
                    ->orWhereNull('features');
            }),
            'parcial'  => $query->whereRaw(
                "features @> ?::jsonb AND NOT (
                    (SELECT bool_and(value::text = 'true')
                     FROM jsonb_each(features->?)
                     WHERE key != 'activo')
                )",
                [json_encode([$modulo => ['activo' => true]]), $modulo]
            ),
            default    => $query,
        };
    }

    /**
     * Filtro combinado: texto + módulo + plan + activo.
     * Todos los parámetros son opcionales — solo aplica los que vienen.
     *
     * @param array{
     *   texto?: string,
     *   modulo?: string,
     *   estado_modulo?: string,
     *   plan?: string,
     *   activo?: bool
     * } $filtros
     */
    public function filtrarCombinado(array $filtros): Builder
    {
        $query = Empresa::query();

        if (! empty($filtros['texto'])) {
            $q = trim($filtros['texto']);
            $query->where(function (Builder $b) use ($q) {
                $b->whereRaw("name % ?", [$q])
                    ->orWhereRaw("email ILIKE ?", ["%{$q}%"])
                    ->orWhereRaw("numero_identificacion ILIKE ?", ["{$q}%"]);
            });
        }

        if (! empty($filtros['modulo']) && ! empty($filtros['estado_modulo'])) {
            $query = $this->filtrarPorModulo($query, $filtros['modulo'], $filtros['estado_modulo']);
        }

        if (! empty($filtros['plan'])) {
            $query->where('plan', $filtros['plan']);
        }

        if (isset($filtros['activo'])) {
            $query->where('activo', $filtros['activo']);
        }

        return $query;
    }

    /**
     * Cursor-based pagination — O(1) independiente del volumen de datos.
     * Usar en listas grandes en lugar de OFFSET (que hace full-scan hasta la página).
     *
     * @param  int         $perPage  Registros por página
     * @param  int|null    $cursor   ID del último registro visto (null = primera página)
     */
    public function paginarConCursor(Builder $query, int $perPage = 25, ?int $cursor = null): object
    {
        $q = clone $query;

        if ($cursor !== null) {
            $q->where('empresas.id', '>', $cursor);
        }

        $items    = $q->orderBy('empresas.id')->limit($perPage + 1)->get();
        $hasMore  = $items->count() > $perPage;
        $items    = $hasMore ? $items->take($perPage) : $items;
        $nextCursor = $hasMore ? $items->last()?->id : null;

        return (object) [
            'data'        => $items,
            'next_cursor' => $nextCursor,
            'has_more'    => $hasMore,
        ];
    }

    /**
     * Búsqueda exacta por RUC, cédula u otro número de identificación.
     * Usa índice B-tree — O(log n).
     */
    public function buscarPorIdentificacion(string $numero): ?Empresa
    {
        return Empresa::where('numero_identificacion', $numero)->first();
    }

    /**
     * Devuelve un Builder base de empresas para la tabla del admin:
     * incluye solo columnas necesarias para la lista, sin cargar features completo.
     */
    public function queryListaAdmin(): Builder
    {
        return Empresa::query()
            ->select([
                'id', 'name', 'email', 'slug', 'plan', 'activo',
                'numero_identificacion', 'created_at',
                // Estado resumido de módulos sin cargar el JSONB completo
                DB::raw("(
                    SELECT COUNT(*)
                    FROM jsonb_object_keys(features) AS k
                    WHERE features->k->>'activo' = 'true'
                ) AS modulos_activos_count"),
            ]);
    }
}
