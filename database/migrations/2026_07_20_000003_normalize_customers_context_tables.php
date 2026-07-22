<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Normalización de `customers`: la tabla mezclaba 5 dominios. Se extraen a contexto
 * (tablas angostas 1:1). Migración ADITIVA: crea las tablas y copia los datos; el DROP
 * de las columnas viejas de customers va en una migración posterior, tras repuntar el
 * código.
 *
 *   - Comercio exterior  → customer_export  (es_exportador, pais_destino)
 *   - Acceso al portal   → customer_access  (password, email_verified_at, is_super_admin)
 *   - Contabilidad       → customer_finance (cuenta_contable_id) — la tabla ya existe;
 *                          aquí solo se sincroniza el dato para eliminar el duplicado.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Comercio exterior (solo quien exporta genera fila) ───────────────
        Schema::create('customer_export', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->boolean('es_exportador')->default(false);
            $table->string('pais_destino', 100)->nullable();
            $table->timestamps();

            $table->index('empresa_id');
        });

        // ── Acceso al portal (password, verificación, super admin) ───────────
        Schema::create('customer_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->string('password')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_super_admin')->default(false);
            $table->timestamps();

            $table->index('empresa_id');
        });

        // ── Backfill comercio exterior: solo clientes con dato real ──────────
        DB::statement(<<<'SQL'
            INSERT INTO customer_export (empresa_id, customer_id, es_exportador, pais_destino, created_at, updated_at)
            SELECT empresa_id, id, COALESCE(es_exportador, false), pais_destino, now(), now()
            FROM customers
            WHERE COALESCE(es_exportador, false) = true OR pais_destino IS NOT NULL
            ON CONFLICT (customer_id) DO NOTHING
        SQL);

        // ── Backfill acceso: solo clientes con credencial/rol real ───────────
        DB::statement(<<<'SQL'
            INSERT INTO customer_access (empresa_id, customer_id, password, email_verified_at, is_super_admin, created_at, updated_at)
            SELECT empresa_id, id, password, email_verified_at, COALESCE(is_super_admin, false), now(), now()
            FROM customers
            WHERE password IS NOT NULL OR email_verified_at IS NOT NULL OR COALESCE(is_super_admin, false) = true
            ON CONFLICT (customer_id) DO NOTHING
        SQL);

        // ── Consolidar cuenta contable en customer_finance (elimina duplicado) ──
        // Filas de finance faltantes (clientes creados tras el backfill previo).
        DB::statement(<<<'SQL'
            INSERT INTO customer_finance (empresa_id, customer_id, cuenta_contable_id, saldo, limite_credito, created_at, updated_at)
            SELECT empresa_id, id, cuenta_contable_id, 0, 0, now(), now()
            FROM customers
            ON CONFLICT (customer_id) DO NOTHING
        SQL);
        // Sincronizar la cuenta donde finance aún no la tenga.
        DB::statement(<<<'SQL'
            UPDATE customer_finance f
            SET cuenta_contable_id = c.cuenta_contable_id, updated_at = now()
            FROM customers c
            WHERE c.id = f.customer_id
              AND f.cuenta_contable_id IS NULL
              AND c.cuenta_contable_id IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_access');
        Schema::dropIfExists('customer_export');
        // customer_finance NO se toca aquí: es preexistente.
    }
};
