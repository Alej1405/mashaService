<?php

namespace App\Services;

use App\Models\Empresa;
use Illuminate\Support\Facades\DB;

/**
 * Escritura y gestión de features JSONB por empresa.
 * Responsabilidad única: leer/escribir features + dual-write a booleanos legacy.
 *
 * Para búsquedas → EmpresaSearchService
 * Para estadísticas y consultas pesadas → EmpresaStatsService
 */
class EmpresaFeaturesService
{
    /**
     * Activa o desactiva un feature individual usando jsonb_set en PostgreSQL.
     * Hace dual-write a las columnas booleanas legacy para que el EmpresaObserver
     * siga disparando correctamente (wasChanged() depende de ellas).
     */
    public function setFeature(Empresa $empresa, string $dotPath, bool $value): void
    {
        $pgPath = '{' . str_replace('.', ',', $dotPath) . '}';

        DB::statement(
            "UPDATE empresas SET features = jsonb_set(features, ?, ?::jsonb, true) WHERE id = ?",
            [$pgPath, json_encode($value), $empresa->id]
        );

        $this->syncBooleanColumns($empresa, $dotPath, $value);
        $empresa->refresh();

        app(EmpresaStatsService::class)->invalidarCache();
    }

    /**
     * Activa o desactiva un módulo completo: cambia 'activo' y todas sus sub-features.
     */
    public function setModule(Empresa $empresa, string $module, bool $value): void
    {
        $features    = config("erp_features.{$module}.features", []);
        $subFeatures = array_fill_keys(array_keys($features), $value);

        $patch = array_merge(['activo' => $value], $this->expandDotKeys($subFeatures));

        DB::statement(
            "UPDATE empresas SET features = jsonb_set(features, ?, ?::jsonb, true) WHERE id = ?",
            ["{$module}", json_encode($patch), $empresa->id]
        );

        foreach (array_keys($features) as $featureKey) {
            $this->syncBooleanColumns($empresa, "{$module}.{$featureKey}", $value);
        }
        $this->syncBooleanColumns($empresa, "{$module}.activo", $value);

        $empresa->refresh();

        app(EmpresaStatsService::class)->invalidarCache($module);
    }

    /**
     * Guarda el array de features completo (usado desde el form de edición).
     * También sincroniza los booleanos legacy y dispara el Observer.
     */
    public function saveAllFeatures(Empresa $empresa, array $features): void
    {
        $syncData = $this->buildSyncData($features);
        $empresa->update(array_merge(['features' => $features], $syncData));

        app(EmpresaStatsService::class)->invalidarCache();
    }

    /**
     * Sincroniza las columnas booleanas legacy a partir de un path de feature.
     * Necesario para que el EmpresaObserver siga detectando wasChanged() correctamente.
     */
    private function syncBooleanColumns(Empresa $empresa, string $dotPath, bool $value): void
    {
        $map = [
            'marketing.mailing.activo'    => ['servicio_mailing_activo' => $value],
            'marketing.mailing'           => ['servicio_mailing_activo' => $value],
            'marketing.cms.activo'        => ['servicio_cms_activo' => $value],
            'marketing.cms'               => ['servicio_cms_activo' => $value],
            'inventario.activo'           => ['tipo_operacion_productos' => $value],
            'ventas.activo'               => ['tipo_operacion_productos' => $value],
            'produccion.activo'           => ['tipo_operacion_manufactura' => $value],
            'logistica.activo'            => ['tiene_logistica' => $value],
            'logistica.comercio_exterior' => ['tiene_comercio_exterior' => $value],
        ];

        if (isset($map[$dotPath])) {
            $empresa->update($map[$dotPath]);
        }
    }

    /**
     * Construye el array de columnas booleanas para saveAllFeatures().
     */
    private function buildSyncData(array $features): array
    {
        return [
            'servicio_mailing_activo'    => (bool) data_get($features, 'marketing.mailing.activo', false),
            'servicio_cms_activo'        => (bool) data_get($features, 'marketing.cms.activo', false),
            'tipo_operacion_productos'   => (bool) data_get($features, 'inventario.activo', false),
            'tipo_operacion_servicios'   => (bool) data_get($features, 'produccion.diseno_servicios', false),
            'tipo_operacion_manufactura' => (bool) data_get($features, 'produccion.activo', false),
            'tiene_logistica'            => (bool) data_get($features, 'logistica.activo', false),
            'tiene_comercio_exterior'    => (bool) data_get($features, 'logistica.comercio_exterior', false),
        ];
    }

    /**
     * Convierte dot-notation keys a array anidado.
     */
    private function expandDotKeys(array $flat): array
    {
        $result = [];
        foreach ($flat as $key => $value) {
            data_set($result, $key, $value);
        }
        return $result;
    }
}
