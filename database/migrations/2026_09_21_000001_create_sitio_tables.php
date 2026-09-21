<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas del sitio propio. Llevan empresa_id como el resto del CMS: son
 * contenido de una Empresa, no tablas sueltas. Hoy las usa MashaCorp; si
 * manana un cliente necesita portafolio o planes, ya estan.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Paginas: cada seccion autonoma del sitio (/desarrollo, /fotografia…) ──
        Schema::create('sitio_paginas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('slug', 60);                 // inicio | desarrollo | fotografia | laboratorio | proceso
            $table->string('titulo');
            $table->string('subtitulo')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable();
            $table->longText('cuerpo')->nullable();     // markdown, para /proceso y textos largos
            $table->json('bloques')->nullable();        // [{titulo, texto, icono, imagen}]
            $table->integer('sort_order')->default(0);  // orden de las fuerzas en el inicio
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['empresa_id', 'slug']);
        });

        // ── Casos del portafolio ────────────────────────────────────────
        Schema::create('sitio_casos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('slug', 120);
            $table->string('titulo');
            $table->string('cliente')->nullable();
            $table->string('sector')->nullable();
            $table->string('servicio', 40)->default('desarrollo'); // desarrollo | fotografia
            $table->text('resumen')->nullable();
            $table->text('problema')->nullable();
            $table->text('solucion')->nullable();
            $table->text('resultado')->nullable();
            $table->json('metricas')->nullable();       // [{etiqueta, valor}]
            $table->text('enlace_sitio')->nullable();
            $table->string('imagen_portada')->nullable();
            $table->unsignedInteger('portada_ancho')->nullable();
            $table->unsignedInteger('portada_alto')->nullable();
            // El caso no sale del ERP hasta que el cliente autoriza publicarlo.
            $table->boolean('publicable')->default(false);
            $table->boolean('destacado')->default(false); // el destacado abre expandido
            $table->integer('sort_order')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['empresa_id', 'slug']);
            $table->index(['empresa_id', 'servicio']);
        });

        // ── Galeria de cada caso ────────────────────────────────────────
        Schema::create('sitio_caso_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sitio_caso_id')->constrained('sitio_casos')->cascadeOnDelete();
            $table->string('imagen');
            $table->string('texto_alt')->nullable();
            $table->unsignedInteger('ancho')->nullable();
            $table->unsignedInteger('alto')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ── Planes de precio publicos ───────────────────────────────────
        Schema::create('sitio_planes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('pagina_slug', 60);          // a que seccion pertenece el precio
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('precio_desde', 10, 2)->nullable();
            $table->string('moneda', 3)->default('USD');
            $table->string('periodicidad', 20)->default('unico'); // unico | mensual | anual
            $table->json('incluye')->nullable();        // [{texto}]
            $table->string('nota')->nullable();
            $table->boolean('destacado')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['empresa_id', 'pagina_slug']);
        });

        // ── Metadatos por ruta ──────────────────────────────────────────
        Schema::create('sitio_seo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('ruta', 160);                // '/', '/desarrollo', '/laboratorio'
            $table->string('titulo_meta')->nullable();
            $table->text('descripcion_meta')->nullable();
            $table->string('og_imagen')->nullable();
            $table->string('robots', 60)->default('index,follow');
            $table->json('json_ld')->nullable();
            $table->timestamps();
            $table->unique(['empresa_id', 'ruta']);
        });

        // ── Navegacion (incluye el nav inferior del movil) ──────────────
        Schema::create('sitio_navegacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('etiqueta', 60);
            $table->string('ruta', 160);
            $table->string('icono', 60)->nullable();
            $table->string('ubicacion', 20)->default('superior');  // superior | inferior | pie
            $table->string('dispositivo', 20)->default('ambos');   // ambos | movil | escritorio
            $table->integer('sort_order')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['empresa_id', 'ubicacion']);
        });

        // ── Mensajes del formulario de un solo campo ────────────────────
        Schema::create('sitio_mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('contacto');                 // correo o telefono, el campo unico
            $table->text('mensaje')->nullable();
            $table->string('origen', 160)->nullable();  // ruta del sitio desde donde escribio
            $table->string('ip_hash', 64)->nullable();  // hash, no la IP
            $table->string('user_agent', 255)->nullable();
            $table->boolean('atendido')->default(false);
            $table->timestamp('atendido_en')->nullable();
            $table->timestamps();
            $table->index(['empresa_id', 'atendido']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sitio_mensajes');
        Schema::dropIfExists('sitio_navegacion');
        Schema::dropIfExists('sitio_seo');
        Schema::dropIfExists('sitio_planes');
        Schema::dropIfExists('sitio_caso_imagenes');
        Schema::dropIfExists('sitio_casos');
        Schema::dropIfExists('sitio_paginas');
    }
};
