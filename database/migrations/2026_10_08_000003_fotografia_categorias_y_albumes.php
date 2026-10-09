<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fotografía del sitio propio: categorías y álbumes.
 *
 * Las categorías son las tarjetas de «Qué fotografiamos»; cada álbum cuelga
 * de una categoría y guarda sus fotos en orden. Se cargan las tres
 * categorías que hoy se ven en la web, para que nada cambie al migrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitio_foto_categorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nombre', 80);
            $table->string('slug', 80);
            $table->text('descripcion')->nullable();
            $table->string('para', 160)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['empresa_id', 'slug']);
        });

        Schema::create('sitio_foto_albumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('sitio_foto_categorias')->cascadeOnDelete();
            $table->string('titulo', 150);
            $table->string('slug', 150);
            $table->text('descripcion')->nullable();
            $table->date('fecha')->nullable();
            $table->string('portada')->nullable();
            $table->jsonb('fotos')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['empresa_id', 'slug']);
        });

        $empresaId = DB::table('empresas')->where('slug', config('sitio.empresa_slug'))->value('id');
        if (! $empresaId) {
            return;
        }

        $categorias = [
            ['Producto', 'producto', 'Catálogo, empaque y detalle. Fondo controlado o ambientado, según dónde vaya a usarse.', 'Para tiendas en línea y catálogos'],
            ['Marca', 'marca', 'El espacio, el equipo y el proceso. Es lo que hace que una web no parezca de nadie.', 'Para la página de quiénes somos'],
            ['Evento corporativo', 'evento-corporativo', 'Lanzamientos, ferias y capacitaciones. Solo fotografía.', 'Para prensa y redes'],
        ];

        foreach ($categorias as $i => [$nombre, $slug, $descripcion, $para]) {
            DB::table('sitio_foto_categorias')->insert([
                'empresa_id'  => $empresaId,
                'nombre'      => $nombre,
                'slug'        => $slug,
                'descripcion' => $descripcion,
                'para'        => $para,
                'sort_order'  => $i,
                'activo'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        \App\Support\SitioPropio::renovarSello(config('sitio.empresa_slug'), publicar: false);
    }

    public function down(): void
    {
        Schema::dropIfExists('sitio_foto_albumes');
        Schema::dropIfExists('sitio_foto_categorias');
    }
};
