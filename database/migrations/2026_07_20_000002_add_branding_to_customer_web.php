<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Branding de la landing del punto de venta, controlado por el CLIENTE desde su
 * formulario del portal (/mi-web):
 *   - Colores de marca (3) → columnas en customer_web (1:1, peso trivial).
 *   - Galería de imágenes (máx. 5) → tabla propia customer_web_images, colgada del
 *     cliente. Quien no sube imágenes no genera filas: cero peso muerto en la BDD.
 *
 * El tope de 5 imágenes por cliente se valida en la app (el portal); un límite de
 * conteo por padre no es expresable como CHECK simple sin trigger.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Colores de marca en customer_web ────────────────────────────────
        Schema::table('customer_web', function (Blueprint $table) {
            $table->string('color_primario', 9)->nullable()->after('longitud');
            $table->string('color_secundario', 9)->nullable()->after('color_primario');
            $table->string('color_acento', 9)->nullable()->after('color_secundario');
        });

        // Integridad desde la BDD: si hay valor, debe ser un hex #RRGGBB válido.
        foreach (['color_primario', 'color_secundario', 'color_acento'] as $col) {
            DB::statement(
                "ALTER TABLE customer_web ADD CONSTRAINT customer_web_{$col}_hex_chk ".
                "CHECK ({$col} IS NULL OR {$col} ~ '^#[0-9A-Fa-f]{6}$')"
            );
        }

        // ── Galería de imágenes de la landing (tabla propia) ────────────────
        Schema::create('customer_web_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('imagen');                       // ruta en disco public
            $table->string('alt')->nullable();              // texto alternativo / título
            $table->smallInteger('orden')->default(0);
            $table->timestamps();

            $table->index('empresa_id');
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_web_images');

        foreach (['color_primario', 'color_secundario', 'color_acento'] as $col) {
            DB::statement("ALTER TABLE customer_web DROP CONSTRAINT IF EXISTS customer_web_{$col}_hex_chk");
        }

        Schema::table('customer_web', function (Blueprint $table) {
            $table->dropColumn(['color_primario', 'color_secundario', 'color_acento']);
        });
    }
};
