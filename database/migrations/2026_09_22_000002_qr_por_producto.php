<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Un QR por producto, pegado en su gaveta.
 *
 * El QR lleva una URL corta, no un código suelto: así la cámara del celular lo
 * abre sola, sin app intermedia ni lector propio. El token es lo que viaja
 * impreso, por eso es aleatorio y no el id: un id correlativo deja adivinar el
 * catálogo entero probando números.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('qr_token', 16)->nullable()->unique()->after('codigo');
        });

        // Los ítems que ya existen también necesitan su etiqueta.
        DB::table('inventory_items')->whereNull('qr_token')->orderBy('id')
            ->chunkById(200, function ($items) {
                foreach ($items as $item) {
                    DB::table('inventory_items')->where('id', $item->id)
                        ->update(['qr_token' => Str::lower(Str::random(10))]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('qr_token');
        });
    }
};
