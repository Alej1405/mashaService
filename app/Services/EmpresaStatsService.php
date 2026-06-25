<?php

namespace App\Services;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Consultas pesadas de agregación y estadísticas de empresas.
 * Reutilizable en cualquier panel o API.
 *
 * Todas las queries usan:
 *  - COUNT(*) FILTER  → una sola pasada por la tabla
 *  - CTEs             → evitar subqueries repetidos
 *  - GIN indexes      → filtros JSONB en O(log n)
 *  - Cache::remember  → stats que no cambian en tiempo real
 */
class EmpresaStatsService
{
    /**
     * Stats globales del dashboard: total, activas, por plan, nuevas.
     * Una sola query con múltiples COUNT FILTER — usa índices parciales.
     * TTL: 60 segundos (stats no necesitan ser en tiempo real).
     */
    public function statsGlobales(): object
    {
        return Cache::remember('admin_empresa_stats_globales', 60, function () {
            return DB::selectOne("
                SELECT
                    COUNT(*)                                                                        AS total,
                    COUNT(*) FILTER (WHERE activo)                                                  AS activas,
                    COUNT(*) FILTER (WHERE NOT activo)                                              AS inactivas,
                    COUNT(*) FILTER (WHERE activo AND plan = 'basic')                               AS basic,
                    COUNT(*) FILTER (WHERE activo AND plan = 'pro')                                 AS pro,
                    COUNT(*) FILTER (WHERE activo AND plan = 'enterprise')                          AS enterprise,
                    COUNT(*) FILTER (WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', NOW())) AS nuevas_este_mes
                FROM empresas
            ");
        });
    }

    /**
     * Porcentaje de adopción por módulo: cuántas empresas tienen cada módulo activo.
     * Usa una sola query con COUNT FILTER por módulo + índice GIN JSONB.
     * TTL: 120 segundos.
     */
    public function adopcionPorModulo(): Collection
    {
        $modulos = array_keys(config('erp_features', []));

        if (empty($modulos)) {
            return collect();
        }

        $selects = [];
        $bindings = [];

        foreach ($modulos as $modulo) {
            $selects[] = "COUNT(*) FILTER (WHERE features @> ?::jsonb) AS {$modulo}";
            $bindings[] = json_encode([$modulo => ['activo' => true]]);
        }

        $selects[] = 'COUNT(*) AS total';
        $sql = 'SELECT ' . implode(', ', $selects) . ' FROM empresas WHERE activo = true';

        $cacheKey = 'admin_empresa_adopcion_modulos';

        $raw = Cache::remember($cacheKey, 120, fn () => DB::selectOne($sql, $bindings));

        $total = (int) ($raw->total ?? 1);

        return collect($modulos)->mapWithKeys(function (string $modulo) use ($raw, $total) {
            $count = (int) ($raw->{$modulo} ?? 0);
            return [$modulo => [
                'count'       => $count,
                'total'       => $total,
                'porcentaje'  => $total > 0 ? round(($count / $total) * 100) : 0,
                'label'       => config("erp_features.{$modulo}.label", ucfirst($modulo)),
                'icon'        => config("erp_features.{$modulo}.icon", 'heroicon-o-squares-2x2'),
                'color'       => config("erp_features.{$modulo}.color", 'gray'),
            ]];
        });
    }

    /**
     * Estado de todos los módulos para una empresa — operación en memoria, sin query.
     * El cast 'array' del modelo ya tiene el JSONB deserializado.
     *
     * @return array<string, 'completo'|'parcial'|'inactivo'>
     */
    public function estadoModulosPorEmpresa(Empresa $empresa): array
    {
        $resultado = [];

        foreach (array_keys(config('erp_features', [])) as $modulo) {
            $resultado[$modulo] = $empresa->moduleStatus($modulo);
        }

        return $resultado;
    }

    /**
     * Builder de empresas filtradas por estado de un módulo específico.
     * Usa el índice GIN JSONB — O(log n).
     * Útil para la vista "Por Módulo".
     *
     * @param string $estado  'activo' | 'parcial' | 'inactivo'
     */
    public function empresasPorEstadoModulo(string $modulo, string $estado): Builder
    {
        $query = Empresa::query();

        return match ($estado) {
            'activo'   => $query->whereRaw(
                "features @> ?::jsonb",
                [json_encode([$modulo => ['activo' => true]])]
            ),
            'inactivo' => $query->where(function (Builder $q) use ($modulo) {
                $q->whereRaw("NOT (features @> ?::jsonb)", [json_encode([$modulo => ['activo' => true]])])
                    ->orWhereNull('features');
            }),
            default    => $query,
        };
    }

    /**
     * Empresas con actividad: resuelve N+1 de sesiones y último login en una sola query.
     * Usa LEFT JOIN con subquery de sessions para calcular usuarios online ahora.
     * Índices usados: idx_users_empresa_login, idx_sessions_user_activity.
     */
    public function empresasConActividad(): Builder
    {
        $threshold = now()->subMinutes(5)->timestamp;

        return Empresa::query()
            ->select(
                'empresas.*',
                DB::raw('COUNT(DISTINCT s.user_id) AS online_count'),
                DB::raw('MAX(u.last_login_at) AS ultimo_login_at')
            )
            ->leftJoin('users AS u', 'u.empresa_id', '=', 'empresas.id')
            ->leftJoinSub(
                DB::table('sessions')
                    ->select('user_id')
                    ->whereNotNull('user_id')
                    ->where('last_activity', '>=', $threshold),
                's',
                's.user_id',
                '=',
                'u.id'
            )
            ->groupBy('empresas.id');
    }

    /**
     * Stats de un módulo específico: cuántas empresas lo tienen, qué sub-features
     * son más populares. Usa CTE para evitar múltiples subqueries.
     * TTL: 300 segundos.
     */
    public function statsDeModulo(string $modulo): object
    {
        $features = array_keys(config("erp_features.{$modulo}.features", []));

        $cacheKey = "admin_stats_modulo_{$modulo}";

        return Cache::remember($cacheKey, 300, function () use ($modulo, $features) {
            $subFeatureSelects = [];
            $bindings          = [
                json_encode([$modulo => ['activo' => true]]),
            ];

            foreach ($features as $featureKey) {
                $col = preg_replace('/[^a-z0-9_]/i', '_', $featureKey);
                $subFeatureSelects[] = "COUNT(*) FILTER (WHERE features @> ?::jsonb) AS sf_{$col}";
                $bindings[] = json_encode([$modulo => [$featureKey => true]]);
            }

            $sfSql = ! empty($subFeatureSelects)
                ? ', ' . implode(', ', $subFeatureSelects)
                : '';

            $sql = "
                WITH base AS (
                    SELECT
                        COUNT(*) FILTER (WHERE features @> ?::jsonb) AS con_modulo,
                        COUNT(*) AS total
                        {$sfSql}
                    FROM empresas
                    WHERE activo = true
                )
                SELECT * FROM base
            ";

            $raw = DB::selectOne($sql, $bindings);

            $subFeaturesStats = [];
            foreach ($features as $featureKey) {
                $col = 'sf_' . preg_replace('/[^a-z0-9_]/i', '_', $featureKey);
                $subFeaturesStats[$featureKey] = (int) ($raw->{$col} ?? 0);
            }

            return (object) [
                'modulo'         => $modulo,
                'con_modulo'     => (int) ($raw->con_modulo ?? 0),
                'total_empresas' => (int) ($raw->total ?? 0),
                'sub_features'   => $subFeaturesStats,
            ];
        });
    }

    /**
     * Invalida todos los caches de stats.
     * Llamar cuando se modifica activamente las features de una empresa.
     */
    public function invalidarCache(?string $modulo = null): void
    {
        Cache::forget('admin_empresa_stats_globales');
        Cache::forget('admin_empresa_adopcion_modulos');

        if ($modulo) {
            Cache::forget("admin_stats_modulo_{$modulo}");
        }
    }
}
