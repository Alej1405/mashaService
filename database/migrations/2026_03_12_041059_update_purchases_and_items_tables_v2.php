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
        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('tipo_pago', ['contado', 'credito_local', 'credito_exterior'])->default('contado')->after('status');
            $table->date('fecha_vencimiento')->nullable()->after('tipo_pago');
            $table->decimal('subtotal', 15, 4)->default(0)->after('fecha_vencimiento');
            $table->decimal('iva', 15, 4)->default(0)->after('subtotal');
            $table->text('notas')->nullable()->after('total');
            $table->foreignId('confirmado_por')->nullable()->constrained('users')->after('notas');
            $table->timestamp('confirmado_at')->nullable()->after('confirmado_por');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->boolean('aplica_iva')->default(true)->after('unit_price');
            $table->decimal('subtotal', 15, 4)->default(0)->after('aplica_iva');
            $table->decimal('iva_monto', 15, 4)->default(0)->after('subtotal');
        });
    }

    public function down(): void
    {
        //
    }
};
