<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retira las columnas imagen_principal/galeria de store_products: las imágenes
 * viven SOLO en product_images (tabla normalizada). Antes de dropear, re-sincroniza
 * cualquier imagen suelta que aún no esté en product_images.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tieneImg = Schema::hasColumn('store_products', 'imagen_principal');
        $tieneGal = Schema::hasColumn('store_products', 'galeria');

        if ($tieneImg || $tieneGal) {
            foreach (DB::table('store_products')->get() as $p) {
                // Ya migrado (tiene imágenes en la tabla) → no duplicar.
                if (DB::table('product_images')->where('store_product_id', $p->id)->exists()) {
                    continue;
                }

                $orden = 0;
                $now   = now();

                if ($tieneImg && ! empty($p->imagen_principal)) {
                    DB::table('product_images')->insert([
                        'empresa_id' => $p->empresa_id, 'store_product_id' => $p->id,
                        'path' => $p->imagen_principal, 'es_principal' => true, 'orden' => $orden++,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }

                if ($tieneGal) {
                    $galeria = is_array($p->galeria) ? $p->galeria : (json_decode($p->galeria ?? '[]', true) ?: []);
                    foreach ($galeria as $path) {
                        if (empty($path)) {
                            continue;
                        }
                        DB::table('product_images')->insert([
                            'empresa_id' => $p->empresa_id, 'store_product_id' => $p->id,
                            'path' => $path, 'es_principal' => false, 'orden' => $orden++,
                            'created_at' => $now, 'updated_at' => $now,
                        ]);
                    }
                }
            }
        }

        Schema::table('store_products', function (Blueprint $table) {
            if (Schema::hasColumn('store_products', 'imagen_principal')) {
                $table->dropColumn('imagen_principal');
            }
            if (Schema::hasColumn('store_products', 'galeria')) {
                $table->dropColumn('galeria');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_products', function (Blueprint $table) {
            if (! Schema::hasColumn('store_products', 'imagen_principal')) {
                $table->string('imagen_principal')->nullable();
            }
            if (! Schema::hasColumn('store_products', 'galeria')) {
                $table->json('galeria')->nullable();
            }
        });
    }
};
