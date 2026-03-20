<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // URL del logo de cliente puede ser muy larga
        Schema::table('cms_client_logos', function (Blueprint $table) {
            $table->text('url')->nullable()->change();
        });

        // CTA del hero puede ser una URL larga
        Schema::table('cms_heroes', function (Blueprint $table) {
            $table->text('cta_url')->nullable()->change();
        });

        // Redes sociales en contacto pueden contener URLs largas
        Schema::table('cms_contacts', function (Blueprint $table) {
            $table->text('facebook')->nullable()->change();
            $table->text('instagram')->nullable()->change();
            $table->text('linkedin')->nullable()->change();
            $table->text('youtube')->nullable()->change();
            $table->text('tiktok')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cms_client_logos', function (Blueprint $table) {
            $table->string('url')->nullable()->change();
        });

        Schema::table('cms_heroes', function (Blueprint $table) {
            $table->string('cta_url')->nullable()->change();
        });

        Schema::table('cms_contacts', function (Blueprint $table) {
            $table->string('facebook')->nullable()->change();
            $table->string('instagram')->nullable()->change();
            $table->string('linkedin')->nullable()->change();
            $table->string('youtube')->nullable()->change();
            $table->string('tiktok')->nullable()->change();
        });
    }
};
