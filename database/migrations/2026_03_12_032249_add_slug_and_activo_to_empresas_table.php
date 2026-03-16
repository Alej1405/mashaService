<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('slug')->unique()->nullable()->after('name');
            $table->boolean('activo')->default(true)->after('email');
        });

        // Inicializar slugs para empresas existentes
        \App\Models\Empresa::all()->each(function ($empresa) {
            $empresa->update([
                'slug' => \Illuminate\Support\Str::slug($empresa->name) . '-' . $empresa->id,
                'activo' => true
            ]);
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn(['slug', 'activo']);
        });
    }
};
