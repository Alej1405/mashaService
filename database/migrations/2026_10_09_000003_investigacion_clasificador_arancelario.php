<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tercera investigación del laboratorio: el clasificador arancelario.
 * Usa la puerta pública clasificador-publico.srv1666598.hstgr.cloud.
 */
return new class extends Migration
{
    public function up(): void
    {
        $empresaId = DB::table('empresas')->where('slug', config('sitio.empresa_slug'))->value('id');
        if (! $empresaId || DB::table('sitio_investigaciones')->where('empresa_id', $empresaId)->where('slug', 'clasificador-arancelario')->exists()) {
            return;
        }

        DB::table('sitio_investigaciones')->insert([
            'empresa_id'  => $empresaId,
            'titulo'      => 'Clasificador arancelario',
            'slug'        => 'clasificador-arancelario',
            'resumen'     => 'Describe un producto en palabras y obtén la subpartida del arancel del Ecuador que le corresponde, con sus tributos, sus controles previos y lo que cambiaría la clasificación.',
            'para_quien'  => 'Importadores, agentes de carga y de aduana, y desarrolladores que necesitan un motor de búsqueda arancelaria sin inteligencia artificial: algoritmos que se pueden auditar.',
            'cuerpo'      => '<h2>Qué hace</h2><p>Escribes lo que quieres importar —«zapatos de cuero», «bomba de agua de 1 HP»— y el servicio busca en el Arancel del Ecuador. Si la descripción alcanza para una sola partida, propone la subpartida del caso general y lista los casos que la cambiarían. Si no alcanza, pide el dato que falta: la medida, el uso, cómo viene presentado.</p><h2>Cómo está hecho</h2><ul><li>Java y Spring Boot sobre PostgreSQL.</li><li>Búsqueda de texto completo en español, similitud por trigramas (pg_trgm), sinónimos curados a seis dígitos y rangos de medidas. Sin inteligencia artificial: cada resultado se puede explicar.</li><li>Los algoritmos viven aislados en su propio paquete para poder revisarlos.</li></ul><h2>Lo que hay que saber antes de usarlo</h2><p>Un clasificador por texto <strong>propone</strong> subpartidas; la clasificación formal sale de aplicar las Reglas Generales Interpretativas con las notas de sección y de capítulo. El resultado es referencial y la responsabilidad es del declarante. Cuando la clasificación es dudosa, la salida correcta es la resolución anticipada del SENAE, que es gratuita y vinculante.</p><p>El arancel cargado es el anexo de la Resolución COMEX 002-2023; las enmiendas de 2026 todavía no están incorporadas. Los tributos se muestran como porcentajes, sin montos.</p>',
            'stack'       => json_encode(['Java', 'Spring Boot', 'PostgreSQL', 'pg_trgm'], JSON_UNESCAPED_UNICODE),
            'estado'      => 'en_vivo',
            'herramienta' => 'clasificador',
            'servicio_url'=> 'https://clasificador-publico.srv1666598.hstgr.cloud',
            'seo_titulo'  => 'Clasificador arancelario del Ecuador: subpartida, tributos y controles',
            'seo_descripcion' => 'Describe tu producto y obtén la subpartida del arancel ecuatoriano con sus tributos y controles previos. Algoritmos auditables, sin IA. Microservicio de MashaCorp.',
            'sort_order'  => 2,
            'activo'      => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        \App\Support\SitioPropio::renovarSello(config('sitio.empresa_slug'), publicar: false);
    }

    public function down(): void
    {
        //
    }
};
