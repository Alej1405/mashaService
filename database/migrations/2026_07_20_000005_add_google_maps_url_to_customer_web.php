<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link de Google Maps del punto de venta. El cliente lo pega en su portal; de ahí se
 * extraen lat/long y además se conserva para un botón "Ver en Google Maps" en la landing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_web', function (Blueprint $table) {
            $table->string('google_maps_url', 500)->nullable()->after('longitud');
        });
    }

    public function down(): void
    {
        Schema::table('customer_web', function (Blueprint $table) {
            $table->dropColumn('google_maps_url');
        });
    }
};
