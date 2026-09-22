<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cimientos del kardex valorado.
 *
 * Hoy el costo de una salida sale de inventory_items.purchase_price, que es el
 * último precio conocido y no es ningún método admitido por NIIF. Esta migración
 * agrega lo que falta para llevar promedio ponderado de verdad:
 *
 *   - el movimiento sabe qué es, dónde ocurre y con qué costo
 *   - cada fila guarda el saldo que dejó, para no recalcular la historia entera
 *   - el stock pasa a vivir por ítem Y ubicación, no solo por ítem
 *
 * Todo es aditivo: lo que ya escribe movimientos sigue funcionando igual.
 */
return new class extends Migration
{
    /** Motivos reales de un movimiento. 'type' (entrada/salida) se conserva. */
    private const MOTIVOS = [
        'compra', 'venta', 'consumo_produccion', 'ingreso_produccion',
        'ajuste_sobrante', 'ajuste_faltante', 'baja', 'traslado', 'saldo_inicial',
    ];

    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // El método es de la empresa, no del usuario ni de la transacción.
            $table->string('metodo_valoracion', 20)->default('promedio')->after('plan');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('costo_promedio', 15, 4)->default(0)->after('purchase_price');
            $table->decimal('saldo_valorado', 15, 4)->default(0)->after('costo_promedio');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->string('motivo', 30)->nullable()->after('type');
            $table->foreignId('almacen_id')->nullable()->after('inventory_item_id')->constrained('almacenes')->nullOnDelete();
            $table->foreignId('ubicacion_almacen_id')->nullable()->after('almacen_id')->constrained('ubicaciones_almacen')->nullOnDelete();
            // Saldo que deja el movimiento: el kardex se lee sin recalcular.
            $table->decimal('saldo_cantidad', 16, 4)->nullable()->after('total');
            $table->decimal('costo_promedio', 15, 4)->nullable()->after('saldo_cantidad');
            $table->decimal('saldo_valor', 15, 4)->nullable()->after('costo_promedio');
            $table->string('lote', 60)->nullable()->after('saldo_valor');
            // Una baja sin acta no es deducible: el dato se exige aquí.
            $table->string('acta_numero', 60)->nullable()->after('lote');
            $table->date('acta_fecha')->nullable()->after('acta_numero');
            $table->string('acta_archivo')->nullable()->after('acta_fecha');
            $table->foreignId('user_id')->nullable()->after('acta_archivo')->constrained('users')->nullOnDelete();

            $table->index(['empresa_id', 'inventory_item_id', 'date'], 'inv_mov_kardex_idx');
        });

        // Stock por ítem y ubicación: "dónde está la sal" necesita esta tabla.
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('almacen_id')->constrained('almacenes')->cascadeOnDelete();
            $table->foreignId('ubicacion_almacen_id')->nullable()->constrained('ubicaciones_almacen')->nullOnDelete();
            $table->decimal('cantidad', 16, 4)->default(0);
            $table->timestamps();
            $table->unique(['inventory_item_id', 'almacen_id', 'ubicacion_almacen_id'], 'inv_stock_unico');
            $table->index(['empresa_id', 'ubicacion_almacen_id']);
        });

        // Cierre de periodo: lo presentado a la Superintendencia no se toca más.
        Schema::create('periodos_contables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->timestamp('cerrado_en')->nullable();
            $table->foreignId('cerrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['empresa_id', 'anio', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_contables');
        Schema::dropIfExists('inventory_stocks');

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex('inv_mov_kardex_idx');
            $table->dropConstrainedForeignId('almacen_id');
            $table->dropConstrainedForeignId('ubicacion_almacen_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['motivo', 'saldo_cantidad', 'costo_promedio', 'saldo_valor', 'lote', 'acta_numero', 'acta_fecha', 'acta_archivo']);
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['costo_promedio', 'saldo_valorado']);
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('metodo_valoracion');
        });
    }
};
