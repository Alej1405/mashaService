<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Borra la empresa AlemCargo con todos sus datos. Era la única que usaba
 * logística (ya fuera del ERP) y no opera con el ERP. Decisión de Ale, 2026-10-06.
 *
 * El orden importa: las ventas apuntan a sus asientos, y las líneas de asiento a
 * las cuentas de la empresa. Si se borra la empresa primero, la cascada intenta
 * quitar las cuentas antes que las líneas y falla. El resto cae por cascada.
 * Sus usuarios propios se borran; quien tenga acceso por pivote solo lo pierde.
 * Sin respaldo, por decisión de Ale: no se va a necesitar.
 */
return new class extends Migration
{
    public function up(): void
    {
        $empresaId = DB::table('empresas')->where('slug', 'alemcargo')->value('id');
        if (! $empresaId) {
            return;
        }

        $asientos = DB::table('journal_entries')->where('empresa_id', $empresaId)->pluck('id');

        DB::table('sales')->where('empresa_id', $empresaId)->delete();
        DB::table('journal_entry_lines')->whereIn('journal_entry_id', $asientos)->delete();
        DB::table('journal_entries')->whereIn('id', $asientos)->delete();

        $usuariosPropios = DB::table('users')->where('empresa_id', $empresaId)
            ->whereNotExists(fn ($q) => $q->from('empresa_user_access')
                ->whereColumn('empresa_user_access.user_id', 'users.id')
                ->where('empresa_user_access.empresa_id', '!=', $empresaId))
            ->pluck('id');

        DB::table('empresas')->where('id', $empresaId)->delete();
        DB::table('users')->whereIn('id', $usuariosPropios)->delete();
    }

    public function down(): void
    {
        // Solo avanzamos.
    }
};
