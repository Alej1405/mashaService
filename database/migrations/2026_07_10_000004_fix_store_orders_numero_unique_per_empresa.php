<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multitenancy: el número de pedido se genera POR empresa (StoreOrder::boot cuenta
 * el último pedido de la misma empresa), pero el UNIQUE estaba sobre `numero` de
 * forma GLOBAL, así que dos empresas colisionaban en ECO-2026-00001.
 *
 * Se reemplaza por un único compuesto (empresa_id, numero): la unicidad correcta
 * en este ERP es siempre por empresa.
 */
return new class extends Migration
{
    public function up(): void
    {
        // El unique global sobre `numero` puede tener distinto nombre según el
        // origen de la BD: Laravel lo crea como `store_orders_numero_unique`, pero
        // la migración pgloader (MariaDB→PostgreSQL) lo dejó como
        // `idx_19131_store_orders_numero_unique`. Se dropean ambos de forma
        // idempotente antes de crear el compuesto correcto (empresa_id, numero).
        DB::statement('DROP INDEX IF EXISTS store_orders_numero_unique');
        DB::statement('DROP INDEX IF EXISTS idx_19131_store_orders_numero_unique');

        Schema::table('store_orders', function (Blueprint $table) {
            $table->unique(['empresa_id', 'numero'], 'store_orders_empresa_numero_unq');
        });
    }

    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            $table->dropUnique('store_orders_empresa_numero_unq');
            $table->unique('numero', 'store_orders_numero_unique');
        });
    }
};
