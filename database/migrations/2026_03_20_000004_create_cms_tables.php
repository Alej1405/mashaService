<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Sección Hero (una por empresa) ──────────────────────────────
        Schema::create('cms_heroes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('titulo')->default('');
            $table->string('subtitulo')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('imagen')->nullable();
            $table->string('cta_texto')->nullable();
            $table->string('cta_url')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique('empresa_id');
        });

        // ── Sección Nosotros (una por empresa) ──────────────────────────
        Schema::create('cms_abouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('titulo')->default('');
            $table->longText('cuerpo')->nullable();
            $table->string('imagen')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique('empresa_id');
        });

        // ── Sección Contacto (una por empresa) ──────────────────────────
        Schema::create('cms_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp')->nullable();
            $table->text('mapa_embed')->nullable();
            $table->string('facebook')->nullable();
            $table->string('instagram')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('youtube')->nullable();
            $table->string('tiktok')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique('empresa_id');
        });

        // ── Servicios (múltiples por empresa) ───────────────────────────
        Schema::create('cms_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('icono')->nullable();
            $table->string('imagen')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // ── Equipo (múltiples por empresa) ──────────────────────────────
        Schema::create('cms_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('cargo')->nullable();
            $table->text('bio')->nullable();
            $table->string('foto')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // ── Logos de clientes (múltiples por empresa) ───────────────────
        Schema::create('cms_client_logos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('logo')->nullable();
            $table->string('url')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // ── Testimonios (múltiples por empresa) ─────────────────────────
        Schema::create('cms_testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('autor_nombre');
            $table->string('autor_cargo')->nullable();
            $table->string('autor_empresa')->nullable();
            $table->string('autor_foto')->nullable();
            $table->text('contenido');
            $table->unsignedTinyInteger('estrellas')->default(5);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // ── FAQ (múltiples por empresa) ──────────────────────────────────
        Schema::create('cms_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('pregunta');
            $table->text('respuesta');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // ── Noticias / Blog (múltiples por empresa) ──────────────────────
        Schema::create('cms_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('titulo');
            $table->string('slug');
            $table->longText('contenido');
            $table->string('imagen')->nullable();
            $table->timestamp('publicado_en')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['empresa_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cms_posts');
        Schema::dropIfExists('cms_faqs');
        Schema::dropIfExists('cms_testimonials');
        Schema::dropIfExists('cms_client_logos');
        Schema::dropIfExists('cms_team_members');
        Schema::dropIfExists('cms_services');
        Schema::dropIfExists('cms_contacts');
        Schema::dropIfExists('cms_abouts');
        Schema::dropIfExists('cms_heroes');
    }
};
