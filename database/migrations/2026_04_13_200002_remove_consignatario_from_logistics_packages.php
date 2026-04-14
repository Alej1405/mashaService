<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_packages', function (Blueprint $table) {
            $table->dropForeign(['consignatario_id']);
            $table->dropColumn('consignatario_id');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_packages', function (Blueprint $table) {
            $table->foreignId('consignatario_id')
                ->nullable()
                ->constrained('logistics_consignatarios')
                ->nullOnDelete();
        });
    }
};
