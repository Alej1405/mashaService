<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Producción bajo pedido, registrada por jornadas.
 *
 * Es alimento: cada pedido guarda quién trabajó, cuándo, con cuántas personas y
 * con qué lote se entrega cada producto. La mano de obra se calcula con la
 * tarifa por hora vigente al abrir la jornada (queda guardada en la jornada).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->decimal('costo_hora_mano_obra', 10, 2)->nullable();
        });

        Schema::table('production_orders', function (Blueprint $table) {
            $table->timestampTz('inicio_real')->nullable();
            $table->timestampTz('fin_real')->nullable();
            $table->string('lote', 60)->nullable();
            $table->text('notas_cierre')->nullable();
            $table->foreignId('terminado_por')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('produccion_jornadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('store_order_id')->constrained('store_orders')->restrictOnDelete();
            $table->unsignedSmallInteger('numero');
            $table->timestampTz('inicio');
            $table->timestampTz('fin')->nullable();
            $table->unsignedSmallInteger('personas');
            $table->decimal('tarifa_hora', 10, 2);
            $table->decimal('horas', 8, 2)->nullable();
            $table->decimal('costo_mano_obra', 12, 2)->nullable();
            $table->foreignId('iniciada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cerrada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['store_order_id', 'numero']);
        });

        DB::statement('ALTER TABLE produccion_jornadas ADD CONSTRAINT produccion_jornadas_personas_check CHECK (personas > 0)');

        // El pedido terminado de fabricar, antes de entregarse.
        DB::statement('ALTER TABLE store_orders DROP CONSTRAINT IF EXISTS store_orders_estado_check');
        DB::statement("ALTER TABLE store_orders ADD CONSTRAINT store_orders_estado_check CHECK (estado = ANY (ARRAY['pendiente','pagado','receptado','procesando','producido','enviado','entregado','cancelado']))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE store_orders DROP CONSTRAINT IF EXISTS store_orders_estado_check');
        DB::statement("ALTER TABLE store_orders ADD CONSTRAINT store_orders_estado_check CHECK (estado = ANY (ARRAY['pendiente','pagado','receptado','procesando','enviado','entregado','cancelado']))");
        Schema::dropIfExists('produccion_jornadas');
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('terminado_por');
            $table->dropColumn(['inicio_real', 'fin_real', 'lote', 'notas_cierre']);
        });
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('costo_hora_mano_obra');
        });
    }
};
