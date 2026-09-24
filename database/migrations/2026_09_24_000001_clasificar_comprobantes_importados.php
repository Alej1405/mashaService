<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Los comprobantes que se bajan del portal hay que clasificarlos.
 *
 * El listado del SRI dice quién facturó y por cuánto, pero no qué se compró.
 * Mientras no se diga si esa factura fue materia prima, un gasto o un activo,
 * el comprobante suma al 104 y no existe en la contabilidad: el balance y la
 * declaración no cuadran, y el mes no se puede cerrar.
 *
 * Al clasificarlo se crea el documento que corresponde —una compra con su
 * kardex, un gasto, un activo fijo— y con él su asiento. De ahí que haya dos
 * caminos para que algo entre al inventario: el módulo de bodega, o una
 * factura que llegó del portal.
 *
 * Marco: skill `contabilidad-ec` — ninguna operación existe sin su asiento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comprobantes_sri', function (Blueprint $table) {
            if (! Schema::hasColumn('comprobantes_sri', 'destino')) {
                // inventario · gasto · activo_fijo · no_deducible
                $table->string('destino', 20)->nullable()->after('desglose');
            }

            if (! Schema::hasColumn('comprobantes_sri', 'tipo_gasto_id')) {
                $table->foreignId('tipo_gasto_id')->nullable()->after('destino')
                    ->constrained('tipos_gasto')->nullOnDelete();
            }

            if (! Schema::hasColumn('comprobantes_sri', 'inventory_item_id')) {
                $table->foreignId('inventory_item_id')->nullable()->after('tipo_gasto_id')
                    ->constrained('inventory_items')->nullOnDelete();
            }

            if (! Schema::hasColumn('comprobantes_sri', 'cantidad')) {
                // El portal no dice cuántas unidades vinieron: lo pone quien clasifica.
                $table->decimal('cantidad', 15, 4)->nullable()->after('inventory_item_id');
            }

            if (! Schema::hasColumn('comprobantes_sri', 'clasificado_en')) {
                $table->timestamp('clasificado_en')->nullable()->after('cantidad');
            }

            if (! Schema::hasColumn('comprobantes_sri', 'clasificado_por')) {
                $table->foreignId('clasificado_por')->nullable()->after('clasificado_en')
                    ->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('comprobantes_sri', function (Blueprint $table) {
            $table->index(['empresa_id', 'destino']);
        });
    }

    public function down(): void
    {
        Schema::table('comprobantes_sri', function (Blueprint $table) {
            $table->dropColumn(['destino', 'tipo_gasto_id', 'inventory_item_id',
                                'cantidad', 'clasificado_en', 'clasificado_por']);
        });
    }
};
