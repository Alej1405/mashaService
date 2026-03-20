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
        Schema::table('carta_presentaciones', function (Blueprint $table) {
            $table->string('template')->default('ejecutivo')->after('color_fondo');
        });
    }

    public function down(): void
    {
        Schema::table('carta_presentaciones', function (Blueprint $table) {
            $table->dropColumn('template');
        });
    }
};
