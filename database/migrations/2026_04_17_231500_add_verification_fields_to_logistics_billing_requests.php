<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logistics_billing_requests', function (Blueprint $table) {
            $table->foreignId('sale_id')
                  ->nullable()
                  ->after('notas')
                  ->constrained('sales')
                  ->nullOnDelete();
            $table->unsignedBigInteger('verificado_por')->nullable()->after('sale_id');
            $table->timestamp('verificado_at')->nullable()->after('verificado_por');
        });
    }

    public function down(): void
    {
        Schema::table('logistics_billing_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sale_id');
            $table->dropColumn(['verificado_por', 'verificado_at']);
        });
    }
};
