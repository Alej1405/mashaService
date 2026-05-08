<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('error_contable')->default(false)->after('journal_entry_id');
            $table->text('error_contable_msg')->nullable()->after('error_contable');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->boolean('error_contable')->default(false)->after('journal_entry_id');
            $table->text('error_contable_msg')->nullable()->after('error_contable');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['error_contable', 'error_contable_msg']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['error_contable', 'error_contable_msg']);
        });
    }
};
