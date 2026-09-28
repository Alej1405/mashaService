<?php

use App\Models\Empresa;
use App\Models\SitioPagina;
use Illuminate\Database\Migrations\Migration;

/**
 * La página del ERP en el sitio propio.
 *
 * `/erp` existía en la web pero no en el panel: la API devolvía 404 y todo su
 * texto estaba escrito en el código del front, así que no se podía editar
 * desde ningún lado. Esto la crea con el contenido que ya se estaba
 * publicando, para que desde ahora se edite en /admin como las demás.
 *
 * Es idempotente: si alguien ya la creó a mano, no la toca.
 */
return new class extends Migration
{
    public function up(): void
    {
        $empresa = Empresa::where('slug', config('sitio.empresa_slug'))->first();

        if (! $empresa) {
            return;
        }

        SitioPagina::withoutGlobalScopes()->firstOrCreate(
            ['empresa_id' => $empresa->id, 'slug' => 'erp'],
            [
                'titulo'      => 'El panel que te entregamos.',
                'subtitulo'   => 'El ERP',
                'descripcion' => 'Cada sitio se entrega con un panel donde cambias cualquier texto, '
                    . 'cualquier foto y cualquier enlace. Sin llamarnos, sin cotizar un cambio '
                    . 'de una línea y sin esperar a que alguien tenga tiempo.',
                'activo'      => true,
                'sort_order'  => 40,
            ],
        );
    }

    public function down(): void
    {
        $empresa = Empresa::where('slug', config('sitio.empresa_slug'))->first();

        if (! $empresa) {
            return;
        }

        SitioPagina::withoutGlobalScopes()
            ->where('empresa_id', $empresa->id)
            ->where('slug', 'erp')
            ->delete();
    }
};
