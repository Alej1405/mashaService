<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Del portal del SRI a la contabilidad, de una sola pasada.
 *
 * - gastos: con qué se pagó (caja, banco o tarjeta), igual que las compras.
 *   Antes todo gasto quedaba "por pagar" aunque se hubiera pagado en efectivo.
 * - comprobantes_sri: cómo se pagó cada factura, para proponer lo mismo en las
 *   siguientes del mismo proveedor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos', function (Blueprint $t) {
            if (! Schema::hasColumn('gastos', 'bank_account_id')) {
                $t->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            }
            if (! Schema::hasColumn('gastos', 'cash_register_id')) {
                $t->foreignId('cash_register_id')->nullable()->constrained('cash_registers')->nullOnDelete();
            }
            if (! Schema::hasColumn('gastos', 'credit_card_id')) {
                $t->foreignId('credit_card_id')->nullable()->constrained('credit_cards')->nullOnDelete();
            }
        });

        Schema::table('comprobantes_sri', function (Blueprint $t) {
            if (! Schema::hasColumn('comprobantes_sri', 'pago')) {
                // credito | efectivo:<caja> | transferencia:<cuenta> | tarjeta:<tarjeta>
                $t->string('pago', 40)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $t) {
            $t->dropConstrainedForeignId('bank_account_id');
            $t->dropConstrainedForeignId('cash_register_id');
            $t->dropConstrainedForeignId('credit_card_id');
        });
        Schema::table('comprobantes_sri', fn (Blueprint $t) => $t->dropColumn('pago'));
    }
};
