<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Habilitar extensión pg_trgm para búsqueda fuzzy por similitud de texto
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        // Índice GIN trigram sobre name — O(log n) para ILIKE y % (similitud)
        DB::statement('
            CREATE INDEX IF NOT EXISTS idx_empresas_name_trgm
            ON empresas USING GIN (name gin_trgm_ops)
        ');

        // Índice B-tree sobre numero_identificacion para búsqueda exacta
        DB::statement('
            CREATE INDEX IF NOT EXISTS idx_empresas_numero_identificacion
            ON empresas (numero_identificacion)
            WHERE numero_identificacion IS NOT NULL
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_empresas_name_trgm');
        DB::statement('DROP INDEX IF EXISTS idx_empresas_numero_identificacion');
    }
};
