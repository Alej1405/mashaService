<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * El sitio propio deja de tener artículos: el laboratorio se arma con
 * Laboratorio · Investigaciones. Decisión de Ale, 2026-10-09.
 *
 * Se borran los artículos de MashaCorp (estaban vacíos, solo título) y los
 * enlaces del menú que llevaban a ellos. Los artículos de los clientes, que
 * viven en la misma tabla, no se tocan.
 */
return new class extends Migration
{
    public function up(): void
    {
        $empresaId = DB::table('empresas')->where('slug', config('sitio.empresa_slug'))->value('id');
        if (! $empresaId) {
            return;
        }

        DB::table('cms_posts')->where('empresa_id', $empresaId)->delete();

        DB::table('sitio_navegacion')
            ->where('empresa_id', $empresaId)
            ->where('etiqueta', 'Artículos')
            ->delete();

        \App\Support\SitioPropio::renovarSello(config('sitio.empresa_slug'), publicar: false);
    }

    public function down(): void
    {
        //
    }
};
