<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migra los datos actuales de `customers` (columnas landing) y de menu a las tablas
 * de módulo recién creadas. Idempotente: se puede re-ejecutar sin duplicar
 * (customer_id es único en cada tabla → ON CONFLICT DO NOTHING).
 *
 * No borra ni modifica nada en `customers`; solo copia. El cutover y el DROP de las
 * columnas viejas se hacen en la fase de formularios del portal.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Web: solo clientes que ya tienen algo de landing ────────────────
        DB::statement(<<<'SQL'
            INSERT INTO customer_web
                (empresa_id, customer_id, publicado, slug, descripcion_web, horario, logo, banner, latitud, longitud, created_at, updated_at)
            SELECT
                empresa_id, id, COALESCE(publicado, false), slug, descripcion_web, horario, logo, banner, latitud, longitud, now(), now()
            FROM customers
            WHERE COALESCE(publicado, false) = true
               OR slug IS NOT NULL
               OR descripcion_web IS NOT NULL
               OR logo IS NOT NULL
               OR banner IS NOT NULL
            ON CONFLICT (customer_id) DO NOTHING
        SQL);

        // ── Menú: clientes con menu_activo o que ya tienen ítems ────────────
        DB::statement(<<<'SQL'
            INSERT INTO customer_menus
                (empresa_id, customer_id, activo, created_at, updated_at)
            SELECT
                c.empresa_id, c.id, COALESCE(c.menu_activo, false), now(), now()
            FROM customers c
            WHERE COALESCE(c.menu_activo, false) = true
               OR EXISTS (SELECT 1 FROM customer_menu_items mi WHERE mi.customer_id = c.id)
            ON CONFLICT (customer_id) DO NOTHING
        SQL);

        // ── Finanzas: 1:1 para TODOS los clientes ───────────────────────────
        DB::statement(<<<'SQL'
            INSERT INTO customer_finance
                (empresa_id, customer_id, cuenta_contable_id, saldo, limite_credito, created_at, updated_at)
            SELECT
                empresa_id, id, cuenta_contable_id, 0, 0, now(), now()
            FROM customers
            ON CONFLICT (customer_id) DO NOTHING
        SQL);
    }

    public function down(): void
    {
        // El rollback de las tablas lo hace la migración de creación; aquí no se
        // borran filas para no perder datos si se re-corre el backfill.
    }
};
