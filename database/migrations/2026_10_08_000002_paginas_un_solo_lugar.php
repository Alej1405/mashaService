<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cada página del sitio propio se edita en un solo lugar: Páginas.
 *
 * - Cómo se comparte (título y descripción para Google y WhatsApp) pasa de
 *   SEO a la página.
 * - El hero del inicio (título, texto, imagen y botón) pasa de Hero a la
 *   página «inicio». La imagen de la página es la única: arriba y al compartir.
 *
 * - Se crean las páginas que la web ya lee y el panel no tenía.
 *
 * Las tablas de origen no se tocan; solo dejan de editarse desde /admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sitio_paginas', function (Blueprint $table) {
            $table->string('seo_titulo', 120)->nullable()->after('descripcion');
            $table->string('seo_descripcion', 300)->nullable()->after('seo_titulo');
        });

        $empresaId = DB::table('empresas')->where('slug', config('sitio.empresa_slug'))->value('id');
        if (! $empresaId) {
            return;
        }

        $paginas = DB::table('sitio_paginas')->where('empresa_id', $empresaId);

        // Las páginas que la web ya lee y no existían en el panel: se crean con
        // el título y la entrada que hoy se ven, sin bloques. Se editan en
        // /admin → Páginas.
        $nuevas = [
            'inicio'         => ['Inicio', null],
            'erp-modulos'    => ['Qué hace el ERP', null],
            'herramientas'   => ['Herramientas que puedes usar ahora.', 'Las cuatro las construimos para trabajar y corren en tu navegador: no piden correo, no guardan nada y no hay que instalar nada. La primera es la araña con la que buscamos clientes.'],
            'microservicios' => ['Los microservicios del ERP.', 'Hay partes del ERP que no viven dentro del ERP. Escalan distinto, fallan aparte y están escritas en otro lenguaje. Estas dos corren ahora mismo en nuestro servidor, y las puedes llamar desde esta página.'],
        ];

        foreach ($nuevas as $slug => [$titulo, $descripcion]) {
            $existe = DB::table('sitio_paginas')->where('empresa_id', $empresaId)->where('slug', $slug)->exists();

            if (! $existe) {
                DB::table('sitio_paginas')->insert([
                    'empresa_id'  => $empresaId,
                    'slug'        => $slug,
                    'titulo'      => $titulo,
                    'descripcion' => $descripcion,
                    'bloques'     => json_encode([]),
                    'sort_order'  => 90,
                    'activo'      => true,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }


        // SEO → la página de su ruta.
        foreach (DB::table('sitio_seo')->where('empresa_id', $empresaId)->get() as $seo) {
            $slug = trim($seo->ruta, '/') ?: 'inicio';
            $pagina = (clone $paginas)->where('slug', $slug)->first();
            if (! $pagina) {
                continue;
            }

            (clone $paginas)->where('id', $pagina->id)->update([
                'seo_titulo'      => $pagina->seo_titulo ?? $seo->titulo_meta,
                'seo_descripcion' => $pagina->seo_descripcion ?? $seo->descripcion_meta,
                'imagen'          => $pagina->imagen ?? $seo->og_imagen,
            ]);
        }

        // Hero → la página «inicio».
        $hero = DB::table('cms_heroes')->where('empresa_id', $empresaId)->where('activo', true)->first();
        $inicio = (clone $paginas)->where('slug', 'inicio')->first();

        if ($hero && $inicio) {
            $bloques = json_decode($inicio->bloques ?? '[]', true) ?: [];
            $tieneBoton = collect($bloques)->contains(fn ($b) => ($b['seccion'] ?? null) === 'apertura' && ($b['tipo'] ?? null) === 'encabezado');

            if (! $tieneBoton && $hero->cta_texto) {
                array_unshift($bloques, [
                    'seccion' => 'apertura',
                    'tipo'    => 'encabezado',
                    'accion'  => $hero->cta_texto,
                    'enlace'  => $hero->cta_url ? parse_url($hero->cta_url, PHP_URL_PATH) . (parse_url($hero->cta_url, PHP_URL_FRAGMENT) ? '#' . parse_url($hero->cta_url, PHP_URL_FRAGMENT) : '') : '/erp#demo',
                    'apoyo'   => 'Sí, puedes escribir encima. Inténtalo.',
                ]);
            }

            (clone $paginas)->where('id', $inicio->id)->update([
                'titulo'          => $hero->titulo ?: $inicio->titulo,
                'descripcion'     => $hero->descripcion ?: $inicio->descripcion,
                'imagen'          => $hero->imagen ?: $inicio->imagen,
                'seo_descripcion' => $inicio->seo_descripcion ?? $hero->subtitulo,
                'bloques'         => json_encode($bloques, JSON_UNESCAPED_UNICODE),
                'updated_at'      => now(),
            ]);
        }

        // Sin publicar desde aquí: la migración corre dentro de una transacción.
        \App\Support\SitioPropio::renovarSello(config('sitio.empresa_slug'), publicar: false);
    }

    public function down(): void
    {
        Schema::table('sitio_paginas', function (Blueprint $table) {
            $table->dropColumn(['seo_titulo', 'seo_descripcion']);
        });
    }
};
