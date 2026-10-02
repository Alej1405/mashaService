<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `empresas.actividades_economicas` pasa de json a jsonb.
 *
 * En Postgres el tipo `json` no tiene operador de igualdad, así que cualquier
 * `SELECT DISTINCT empresas.*` revienta con
 * «could not identify an equality operator for type json».
 *
 * Eso es exactamente lo que genera el AttachAction de Filament al precargar el
 * selector de empresas en la ficha de un usuario: al abrir un usuario con
 * acceso a empresas, la pantalla moría. No se veía antes porque esa consulta
 * solo corre para usuarios que no son super_admin, y no había ninguno.
 *
 * `jsonb` sí define igualdad, es el tipo recomendado en Postgres y es el que ya
 * usaba la otra columna JSON de esta misma tabla (`features`): era una
 * inconsistencia, no una decisión.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Idempotente: en un entorno recién creado la columna ya puede ser
        // jsonb, y una migración que falla por eso tumba el despliegue entero.
        if ($this->tipoActual() === 'json') {
            DB::statement(
                'ALTER TABLE empresas ALTER COLUMN actividades_economicas TYPE jsonb USING actividades_economicas::jsonb'
            );
        }
    }

    public function down(): void
    {
        if ($this->tipoActual() === 'jsonb') {
            DB::statement(
                'ALTER TABLE empresas ALTER COLUMN actividades_economicas TYPE json USING actividades_economicas::json'
            );
        }
    }

    private function tipoActual(): ?string
    {
        return DB::selectOne(
            'select data_type from information_schema.columns where table_name = ? and column_name = ?',
            ['empresas', 'actividades_economicas']
        )?->data_type;
    }
};
