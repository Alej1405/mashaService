<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Deja coherente la navegación de mashaec.net.
 *
 * - «El ERP» lleva a /erp en el menú y en el pie; /erp/modulos queda para
 *   «Qué hace el ERP».
 * - El botón destacado del menú sale del panel (ubicación «accion») con
 *   el texto que ya tenía: «Conversemos».
 * - El correo de contacto se llena si estaba vacío.
 */
return new class extends Migration
{
    public function up(): void
    {
        $empresaId = DB::table('empresas')->where('slug', config('sitio.empresa_slug'))->value('id');
        if (! $empresaId) {
            return;
        }

        $nav = DB::table('sitio_navegacion')->where('empresa_id', $empresaId);

        (clone $nav)->whereIn('ubicacion', ['superior', 'pie'])
            ->where('etiqueta', 'El ERP')
            ->update(['ruta' => '/erp', 'updated_at' => now()]);

        if (! (clone $nav)->where('ubicacion', 'accion')->exists()) {
            DB::table('sitio_navegacion')->insert([
                'empresa_id'  => $empresaId,
                'etiqueta'    => 'Conversemos',
                'ruta'        => '/contacto',
                'ubicacion'   => 'accion',
                'dispositivo' => 'escritorio',
                'sort_order'  => 0,
                'activo'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        DB::table('cms_contacts')
            ->where('empresa_id', $empresaId)
            ->whereNull('email')
            ->update(['email' => 'alejandro@mashacorp.com', 'updated_at' => now()]);
    }

    public function down(): void
    {
        //
    }
};
