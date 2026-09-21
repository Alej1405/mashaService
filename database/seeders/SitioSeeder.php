<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\SitioNavegacion;
use App\Models\SitioPagina;
use App\Models\SitioSeo;
use App\Support\SitioPropio;
use Illuminate\Database\Seeder;

/**
 * Esqueleto del sitio propio: la empresa, sus paginas y su navegacion.
 *
 * Crea la estructura, no el contenido. Los textos, las fotos y los precios
 * se ingresan desde /admin, que es justamente lo que el sitio demuestra.
 *
 *   php artisan db:seed --class=SitioSeeder
 *
 * Es idempotente: correrlo dos veces no duplica nada.
 */
class SitioSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::firstOrCreate(
            ['slug' => SitioPropio::slug()],
            [
                'name'                => 'Masha Corp S.A.S.',
                // La tabla empresas exige correo: es un valor inicial, se
                // corrige desde el panel como cualquier otro dato.
                'email'               => 'alejandro@mashaec.net',
                'activo'              => true,
                'plan'                => 'enterprise',
                'servicio_cms_activo' => true,
            ],
        );

        $paginas = [
            ['slug' => 'inicio',      'titulo' => 'Inicio',      'sort_order' => 0],
            ['slug' => 'desarrollo',  'titulo' => 'Desarrollo',  'sort_order' => 1],
            ['slug' => 'fotografia',  'titulo' => 'Fotografía',  'sort_order' => 2],
            ['slug' => 'laboratorio', 'titulo' => 'Laboratorio', 'sort_order' => 3],
            ['slug' => 'proceso',     'titulo' => 'Proceso',     'sort_order' => 4],
        ];

        foreach ($paginas as $pagina) {
            SitioPagina::withoutGlobalScopes()->firstOrCreate(
                ['empresa_id' => $empresa->id, 'slug' => $pagina['slug']],
                ['titulo' => $pagina['titulo'], 'sort_order' => $pagina['sort_order'], 'activo' => true],
            );
        }

        // Menú superior y pie: escritorio. Barra inferior: la marca de la casa
        // en móvil, por eso lleva ícono y va aparte.
        $navegacion = [
            ['etiqueta' => 'Desarrollo',  'ruta' => '/desarrollo',  'ubicacion' => 'superior', 'icono' => null,          'sort_order' => 1],
            ['etiqueta' => 'Fotografía',  'ruta' => '/fotografia',  'ubicacion' => 'superior', 'icono' => null,          'sort_order' => 2],
            ['etiqueta' => 'Laboratorio', 'ruta' => '/laboratorio', 'ubicacion' => 'superior', 'icono' => null,          'sort_order' => 3],
            ['etiqueta' => 'Inicio',      'ruta' => '/',            'ubicacion' => 'inferior', 'icono' => 'casa',        'sort_order' => 1],
            ['etiqueta' => 'Desarrollo',  'ruta' => '/desarrollo',  'ubicacion' => 'inferior', 'icono' => 'codigo',      'sort_order' => 2],
            ['etiqueta' => 'Fotografía',  'ruta' => '/fotografia',  'ubicacion' => 'inferior', 'icono' => 'camara',      'sort_order' => 3],
            ['etiqueta' => 'Laboratorio', 'ruta' => '/laboratorio', 'ubicacion' => 'inferior', 'icono' => 'matraz',      'sort_order' => 4],
            ['etiqueta' => 'Escríbenos',  'ruta' => '/contacto',    'ubicacion' => 'inferior', 'icono' => 'conversacion','sort_order' => 5],
            ['etiqueta' => 'Proceso',     'ruta' => '/proceso',     'ubicacion' => 'pie',      'icono' => null,          'sort_order' => 1],
        ];

        foreach ($navegacion as $item) {
            SitioNavegacion::withoutGlobalScopes()->firstOrCreate(
                ['empresa_id' => $empresa->id, 'ruta' => $item['ruta'], 'ubicacion' => $item['ubicacion']],
                [
                    'etiqueta'   => $item['etiqueta'],
                    'icono'      => $item['icono'],
                    'sort_order' => $item['sort_order'],
                    'activo'     => true,
                ],
            );
        }

        foreach (['/', '/desarrollo', '/fotografia', '/laboratorio', '/proceso'] as $ruta) {
            SitioSeo::withoutGlobalScopes()->firstOrCreate(
                ['empresa_id' => $empresa->id, 'ruta' => $ruta],
                ['robots' => 'index,follow'],
            );
        }

        $this->command?->info("Sitio propio listo para la empresa '{$empresa->slug}' (id {$empresa->id}).");
    }
}
