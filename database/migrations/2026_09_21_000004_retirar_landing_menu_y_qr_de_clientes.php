<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Retira la landing del punto de venta, su carta y el QR.
 *
 * El cliente ya no tiene página propia: su información se muestra en un modal
 * con lo básico — de qué se trata el local, ubicación y contacto. Todo lo que
 * existía solo para pintar la landing se va, con sus archivos.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->borrarArchivos('customer_menu_items', 'imagen');
        $this->borrarArchivos('customer_web_images', 'imagen');
        $this->borrarArchivos('customer_web', 'banner');

        Schema::dropIfExists('customer_menu_items');
        Schema::dropIfExists('customer_web_images');

        if (Schema::hasColumn('customers', 'menu_activo')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('menu_activo');
            });
        }

        // Los colores y el banner eran identidad visual de la landing.
        // El modal se pinta con el estilo del sitio, no con el del cliente.
        foreach (['color_primario', 'color_secundario', 'color_acento'] as $columna) {
            $restriccion = "customer_web_{$columna}_hex_chk";
            DB::statement("ALTER TABLE customer_web DROP CONSTRAINT IF EXISTS {$restriccion}");
        }

        $sobran = array_values(array_filter(
            ['banner', 'color_primario', 'color_secundario', 'color_acento'],
            fn (string $c): bool => Schema::hasColumn('customer_web', $c),
        ));

        if ($sobran) {
            Schema::table('customer_web', function (Blueprint $table) use ($sobran) {
                $table->dropColumn($sobran);
            });
        }
    }

    /** Borra del disco público los archivos de una columna antes de perder la tabla. */
    private function borrarArchivos(string $tabla, string $columna): void
    {
        if (! Schema::hasTable($tabla) || ! Schema::hasColumn($tabla, $columna)) {
            return;
        }

        $disco = Storage::disk('public');

        DB::table($tabla)->whereNotNull($columna)->pluck($columna)
            ->each(function (?string $ruta) use ($disco): void {
                if ($ruta && $disco->exists($ruta)) {
                    $disco->delete($ruta);
                }
            });
    }

    /**
     * No se revierte: las tablas volverían vacías y los archivos ya no existen.
     * Recrear el esquema sin los datos daría una falsa sensación de vuelta atrás.
     */
    public function down(): void
    {
    }
};
