<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El cliente ES el punto de venta. Se le agregan los campos de su LANDING pública y
 * los toggles que la gobiernan, todo gestionado desde el formulario de Clientes:
 *   - publicado:   si el cliente aparece en la web como punto de venta.
 *   - menu_activo: si se muestra la página de menú (dependiente de la landing).
 *   - slug + datos de vitrina (descripción, horario, logo, banner, ubicación).
 *
 * El menú vive en su propia tabla (ver create_customer_menu_items_table). Aquí NO se
 * duplica nada de la identidad del cliente; solo se enriquece.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('publicado')->default(false)->after('activo');
            $table->boolean('menu_activo')->default(false)->after('publicado');
            $table->string('slug')->nullable()->after('menu_activo');
            $table->text('descripcion_web')->nullable()->after('slug');
            $table->string('horario')->nullable()->after('descripcion_web');
            $table->string('logo')->nullable()->after('horario');
            $table->string('banner')->nullable()->after('logo');
            $table->decimal('latitud', 10, 7)->nullable()->after('banner');
            $table->decimal('longitud', 10, 7)->nullable()->after('latitud');
        });

        // Slug único POR empresa, solo cuando existe (índice parcial de Postgres):
        // permite muchos clientes sin slug y evita choques entre empresas.
        DB::statement('CREATE UNIQUE INDEX customers_empresa_slug_unq ON customers (empresa_id, slug) WHERE slug IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS customers_empresa_slug_unq');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'publicado', 'menu_activo', 'slug', 'descripcion_web',
                'horario', 'logo', 'banner', 'latitud', 'longitud',
            ]);
        });
    }
};
