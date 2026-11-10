<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** El cliente puede dejar una nota en cada producto de su pedido. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_order_items', function (Blueprint $table) {
            $table->string('nota', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('store_order_items', function (Blueprint $table) {
            $table->dropColumn('nota');
        });
    }
};
