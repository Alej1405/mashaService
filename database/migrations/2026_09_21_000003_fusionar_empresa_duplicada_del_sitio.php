<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fusiona la empresa duplicada del sitio propio.
 *
 * El seeder creó 'mashacorp' sin ver que ya existía 'masha-corp-sas', la real:
 * la que tiene RUC, usuario, accesos y el contenido CMS. El contenido del sitio
 * pasa a la real y la duplicada se elimina con lo que el observer le clonó.
 *
 * Es idempotente: si la duplicada ya no existe, no hace nada.
 */
return new class extends Migration
{
    /** Tablas del sitio que hay que reasignar. */
    private const TABLAS = [
        'sitio_paginas',
        'sitio_navegacion',
        'sitio_seo',
        'sitio_casos',
        'sitio_planes',
        'sitio_mensajes',
    ];

    public function up(): void
    {
        $real      = DB::table('empresas')->where('slug', 'masha-corp-sas')->first();
        $duplicada = DB::table('empresas')->where('slug', 'mashacorp')->first();

        if (! $real || ! $duplicada) {
            return;
        }

        DB::transaction(function () use ($real, $duplicada) {
            foreach (self::TABLAS as $tabla) {
                DB::table($tabla)
                    ->where('empresa_id', $duplicada->id)
                    ->update(['empresa_id' => $real->id]);
            }

            // Lo único que la duplicada tenía y la real no.
            DB::table('empresas')->where('id', $real->id)->update(['servicio_cms_activo' => true]);

            // Arrastra las cuentas y mapeos que el EmpresaObserver le clonó.
            DB::table('empresas')->where('id', $duplicada->id)->delete();
        });
    }

    /**
     * No se revierte: volver a partir la empresa en dos recrearía justo el
     * problema que esta migración arregla.
     */
    public function down(): void
    {
    }
};
