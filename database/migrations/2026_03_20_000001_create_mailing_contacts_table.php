<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailing_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('nombre')->default('');
            $table->string('email');
            $table->string('telefono')->nullable();
            $table->text('notas')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'email']);
            $table->index('empresa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_contacts');
    }
};
