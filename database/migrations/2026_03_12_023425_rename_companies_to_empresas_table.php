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
        if (Schema::hasTable('companies') && !Schema::hasTable('empresas')) {
            Schema::rename('companies', 'empresas');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('empresas') && !Schema::hasTable('companies')) {
            Schema::rename('empresas', 'companies');
        }
    }
};
