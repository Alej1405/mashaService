<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CUTOVER FINAL de la landing del cliente. El contenido de la web vive en
 * `customer_web`; las columnas espejo en `customers` quedaron solo como fallback
 * durante la transición. Aquí se reconcilia cualquier dato residual y se ELIMINAN
 * esas columnas de `customers`. Regla: lo viejo, chao — sin columnas muertas.
 *
 * Reconciliación (defensiva para prod, que pudo divergir):
 *   1. Crear la fila de customer_web que falte para clientes con contenido.
 *   2. Rellenar en customer_web los NULL que aún tengan dato en customers
 *      (cubre el hueco del backfill previo, que usó ON CONFLICT DO NOTHING).
 *   3. DROP de las 6 columnas de contenido en customers.
 */
return new class extends Migration
{
    /** Columnas de contenido que se eliminan de customers (viven en customer_web). */
    private array $columns = ['descripcion_web', 'horario', 'logo', 'banner', 'latitud', 'longitud'];

    public function up(): void
    {
        // 1. Filas de customer_web faltantes para clientes que tienen contenido.
        DB::statement(<<<'SQL'
            INSERT INTO customer_web
                (empresa_id, customer_id, descripcion_web, horario, logo, banner, latitud, longitud, created_at, updated_at)
            SELECT
                empresa_id, id, descripcion_web, horario, logo, banner, latitud, longitud, now(), now()
            FROM customers
            WHERE descripcion_web IS NOT NULL
               OR horario IS NOT NULL
               OR logo IS NOT NULL
               OR banner IS NOT NULL
               OR latitud IS NOT NULL
               OR longitud IS NOT NULL
            ON CONFLICT (customer_id) DO NOTHING
        SQL);

        // 2. Rellenar NULLs en customer_web con el dato aún presente en customers.
        DB::statement(<<<'SQL'
            UPDATE customer_web w
            SET descripcion_web = COALESCE(w.descripcion_web, c.descripcion_web),
                horario         = COALESCE(w.horario, c.horario),
                logo            = COALESCE(w.logo, c.logo),
                banner          = COALESCE(w.banner, c.banner),
                latitud         = COALESCE(w.latitud, c.latitud),
                longitud        = COALESCE(w.longitud, c.longitud),
                updated_at      = now()
            FROM customers c
            WHERE c.id = w.customer_id
              AND (
                    (w.descripcion_web IS NULL AND c.descripcion_web IS NOT NULL)
                 OR (w.horario IS NULL AND c.horario IS NOT NULL)
                 OR (w.logo IS NULL AND c.logo IS NOT NULL)
                 OR (w.banner IS NULL AND c.banner IS NOT NULL)
                 OR (w.latitud IS NULL AND c.latitud IS NOT NULL)
                 OR (w.longitud IS NULL AND c.longitud IS NOT NULL)
              )
        SQL);

        // 3. Eliminar las columnas viejas de customers.
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn($this->columns);
        });
    }

    public function down(): void
    {
        // Recrea las columnas vacías (sin restaurar datos: el contenido vive en
        // customer_web). Solo para que el rollback no falle.
        Schema::table('customers', function (Blueprint $table) {
            $table->text('descripcion_web')->nullable();
            $table->string('horario')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
        });
    }
};
