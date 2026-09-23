<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El asiento de un gasto tiene su propio tipo.
 *
 * La restricción de `journal_entries.tipo` no contemplaba los gastos que no
 * pasan por compras de inventario. Meterlos como 'compra' los confundiría en
 * el libro diario y en los informes.
 */
return new class extends Migration
{
    private const TIPOS = [
        'apertura', 'manual', 'compra', 'venta', 'manufactura',
        'ajuste', 'cierre', 'depreciacion', 'cobro_logistico', 'gasto',
    ];

    public function up(): void
    {
        $lista = "'" . implode("','", self::TIPOS) . "'";

        DB::statement('ALTER TABLE journal_entries DROP CONSTRAINT IF EXISTS journal_entries_tipo_check');
        DB::statement("ALTER TABLE journal_entries ADD CONSTRAINT journal_entries_tipo_check CHECK (tipo::text = ANY (ARRAY[{$lista}]::text[]))");
    }

    public function down(): void
    {
        $sinGasto = array_diff(self::TIPOS, ['gasto']);
        $lista = "'" . implode("','", $sinGasto) . "'";

        DB::statement('ALTER TABLE journal_entries DROP CONSTRAINT IF EXISTS journal_entries_tipo_check');
        DB::statement("ALTER TABLE journal_entries ADD CONSTRAINT journal_entries_tipo_check CHECK (tipo::text = ANY (ARRAY[{$lista}]::text[]))");
    }
};
