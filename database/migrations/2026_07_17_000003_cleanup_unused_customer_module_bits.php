<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Limpieza: la fuente de verdad de los TOGGLES y del slug quedó en `customers`
 * (publicado, menu_activo, slug). Por eso:
 *   - `customer_menus` sobra (su toggle vivía duplicado) → se elimina.
 *   - `customer_web` guarda solo CONTENIDO editable por el cliente; sus columnas
 *     publicado/slug sobran → se eliminan.
 * No se toca ningún dato en uso: estas tablas/columnas se acababan de crear.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('customer_menus');

        DB::statement('DROP INDEX IF EXISTS customer_web_empresa_slug_unq');
        Schema::table('customer_web', function (Blueprint $table) {
            $table->dropColumn(['publicado', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('customer_web', function (Blueprint $table) {
            $table->boolean('publicado')->default(false)->after('customer_id');
            $table->string('slug')->nullable()->after('publicado');
        });
        DB::statement('CREATE UNIQUE INDEX customer_web_empresa_slug_unq ON customer_web (empresa_id, slug) WHERE slug IS NOT NULL');

        Schema::create('customer_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->boolean('activo')->default(false);
            $table->string('titulo')->nullable();
            $table->timestamps();
            $table->index('empresa_id');
        });
    }
};
