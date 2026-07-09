<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de imágenes del producto (normalizada: una fila por imagen).
 * Reemplaza a las columnas imagen_principal/galeria de store_products.
 * La landing consulta solo esta tabla cuando necesita imágenes; el resto
 * del producto no las carga.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('store_product_id')->constrained('store_products')->cascadeOnDelete();
            $table->string('path');
            $table->boolean('es_principal')->default(false);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['store_product_id', 'orden']);
            $table->index(['empresa_id', 'store_product_id']);
        });

        // Migrar datos existentes desde store_products.imagen_principal + galeria
        if (Schema::hasColumn('store_products', 'imagen_principal') || Schema::hasColumn('store_products', 'galeria')) {
            foreach (DB::table('store_products')->get() as $p) {
                $orden = 0;
                $now   = now();

                if (! empty($p->imagen_principal)) {
                    DB::table('product_images')->insert([
                        'empresa_id'       => $p->empresa_id,
                        'store_product_id' => $p->id,
                        'path'             => $p->imagen_principal,
                        'es_principal'     => true,
                        'orden'            => $orden++,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]);
                }

                $galeria = is_array($p->galeria) ? $p->galeria : (json_decode($p->galeria ?? '[]', true) ?: []);
                foreach ($galeria as $path) {
                    if (empty($path)) continue;
                    DB::table('product_images')->insert([
                        'empresa_id'       => $p->empresa_id,
                        'store_product_id' => $p->id,
                        'path'             => $path,
                        'es_principal'     => false,
                        'orden'            => $orden++,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
