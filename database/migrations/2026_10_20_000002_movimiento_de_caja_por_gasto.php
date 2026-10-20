<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Un gasto pagado en efectivo (caja chica o caja general) baja el saldo de esa
 * caja con su propio movimiento, origen "gasto". El saldo puede quedar en
 * negativo: pasa mientras se iguala el sistema con la historia de la empresa.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE cash_movements DROP CONSTRAINT IF EXISTS cash_movements_origen_check');
        DB::statement("ALTER TABLE cash_movements ADD CONSTRAINT cash_movements_origen_check CHECK (origen::text = ANY (ARRAY['venta','compra','gasto','transferencia','ajuste','apertura','cierre','manual']::text[]))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE cash_movements DROP CONSTRAINT IF EXISTS cash_movements_origen_check');
        DB::statement("ALTER TABLE cash_movements ADD CONSTRAINT cash_movements_origen_check CHECK (origen::text = ANY (ARRAY['venta','compra','transferencia','ajuste','apertura','cierre','manual']::text[]))");
    }
};
