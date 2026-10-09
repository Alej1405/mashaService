<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * /laboratorio/herramientas y /laboratorio/microservicios dejan de existir:
 * lo que se puede usar ahora vive en Laboratorio · Investigaciones.
 * Decisión de Ale, 2026-10-09. Se borran sus páginas del panel y los
 * enlaces del menú que llevaban a ellas.
 */
return new class extends Migration
{
    public function up(): void
    {
        $empresaId = DB::table('empresas')->where('slug', config('sitio.empresa_slug'))->value('id');
        if (! $empresaId) {
            return;
        }

        DB::table('sitio_paginas')
            ->where('empresa_id', $empresaId)
            ->whereIn('slug', ['herramientas', 'microservicios'])
            ->delete();

        DB::table('sitio_navegacion')
            ->where('empresa_id', $empresaId)
            ->whereIn('ruta', ['/laboratorio/herramientas', '/laboratorio/microservicios'])
            ->delete();

        \App\Support\SitioPropio::renovarSello(config('sitio.empresa_slug'), publicar: false);
    }

    public function down(): void
    {
        //
    }
};
