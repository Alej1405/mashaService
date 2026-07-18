<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Normalización del cliente en módulos (tablas angostas por contexto):
 *   - customer_web:     la landing pública. No todos los clientes la tienen (1:1 opcional).
 *   - customer_menus:   config/toggle del menú del punto de venta (1:1 opcional).
 *                       Los ítems ya viven en customer_menu_items.
 *   - customer_finance: la parte financiera del cliente (1:1).
 *
 * Los PEDIDOS ya son modulares (store_orders + store_order_items → store_products);
 * no se crea nada nuevo para ellos aquí.
 *
 * Estrategia expand/contract: estas tablas se crean y se llenan (ver backfill). El
 * cutover de lecturas/escrituras y el DROP de las columnas viejas de `customers`
 * ocurre en la fase de formularios del portal. Aquí NO se borra nada todavía.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Web / landing pública ────────────────────────────────────────────
        Schema::create('customer_web', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->boolean('publicado')->default(false);      // toggle: activo = se publica
            $table->string('slug')->nullable();
            $table->text('descripcion_web')->nullable();
            $table->string('horario')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->timestamps();

            $table->index('empresa_id');
        });

        // Slug único por empresa, solo cuando existe (índice parcial de Postgres).
        DB::statement('CREATE UNIQUE INDEX customer_web_empresa_slug_unq ON customer_web (empresa_id, slug) WHERE slug IS NOT NULL');

        // ── Menú / catálogo del punto de venta (config + toggle) ─────────────
        Schema::create('customer_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->boolean('activo')->default(false);         // toggle: muestra/oculta el menú
            $table->string('titulo')->nullable();
            $table->timestamps();

            $table->index('empresa_id');
        });

        // ── Finanzas del cliente ─────────────────────────────────────────────
        Schema::create('customer_finance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->foreignId('cuenta_contable_id')->nullable()->constrained('account_plans')->nullOnDelete();
            $table->decimal('saldo', 14, 2)->default(0);
            $table->decimal('limite_credito', 14, 2)->default(0);
            $table->timestamps();

            $table->index('empresa_id');
        });

        DB::statement('ALTER TABLE customer_finance ADD CONSTRAINT customer_finance_limite_no_negativo CHECK (limite_credito >= 0)');

        // ── Promociones en el menú (simple, para restaurante) ────────────────
        Schema::table('customer_menu_items', function (Blueprint $table) {
            $table->boolean('es_promocion')->default(false)->after('activo');
            $table->decimal('precio_promo', 12, 2)->nullable()->after('es_promocion');
        });

        DB::statement('ALTER TABLE customer_menu_items ADD CONSTRAINT customer_menu_items_precio_promo_no_negativo CHECK (precio_promo IS NULL OR precio_promo >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE customer_menu_items DROP CONSTRAINT IF EXISTS customer_menu_items_precio_promo_no_negativo');
        Schema::table('customer_menu_items', function (Blueprint $table) {
            $table->dropColumn(['es_promocion', 'precio_promo']);
        });

        DB::statement('DROP INDEX IF EXISTS customer_web_empresa_slug_unq');
        Schema::dropIfExists('customer_finance');
        Schema::dropIfExists('customer_menus');
        Schema::dropIfExists('customer_web');
    }
};
