<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();

            // Información básica
            $table->string('name');
            $table->string('subject');

            // Encabezado
            $table->string('header_text')->nullable();
            $table->string('header_background_color', 20)->default('#1e40af');
            $table->string('header_text_color', 20)->default('#ffffff');

            // Cuerpo
            $table->longText('body')->nullable();

            // Botón CTA (opcional)
            $table->string('button_text')->nullable();
            $table->string('button_color', 20)->default('#1e40af');
            $table->string('button_text_color', 20)->default('#ffffff');

            // Pie de página
            $table->string('footer_text')->nullable();

            // Tipografía y colores generales
            $table->string('font_family', 60)->default('Arial');
            $table->unsignedSmallInteger('base_font_size')->default(16);
            $table->string('text_color', 20)->default('#374151');

            // Fondo del email
            $table->string('background_color', 20)->default('#f3f4f6');
            $table->string('content_background_color', 20)->default('#ffffff');

            $table->timestamps();

            $table->index('empresa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_templates');
    }
};
