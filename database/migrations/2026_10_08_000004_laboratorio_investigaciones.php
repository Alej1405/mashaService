<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El catálogo del laboratorio: cada investigación es un microservicio que se
 * puede usar desde mashaec.net/laboratorio/investigaciones/<slug>.
 *
 * Se cargan las dos primeras, la araña y formulación, con el texto para
 * desarrolladores que ya pueden ver y editar en /admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sitio_investigaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('titulo', 120);
            $table->string('slug', 80);
            $table->string('resumen', 300)->nullable();
            $table->string('para_quien', 300)->nullable();
            $table->longText('cuerpo')->nullable();
            $table->jsonb('stack')->nullable();
            $table->string('estado', 20)->default('en_vivo');      // en_vivo · en_desarrollo
            $table->string('herramienta', 30)->default('ninguna'); // qué herramienta monta la web
            $table->string('servicio_url')->nullable();
            $table->string('imagen')->nullable();
            $table->string('seo_titulo', 120)->nullable();
            $table->string('seo_descripcion', 300)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['empresa_id', 'slug']);
        });

        $empresaId = DB::table('empresas')->where('slug', config('sitio.empresa_slug'))->value('id');
        if (! $empresaId) {
            return;
        }

        $investigaciones = [
            [
                'titulo'      => 'Araña de búsqueda',
                'slug'        => 'arana',
                'resumen'     => 'Encuentra negocios por rubro y ciudad, visita sus sitios y los califica. También lista los documentos que publica un sitio.',
                'para_quien'  => 'Equipos que hacen prospección, estudios de mercado o auditorías de sitios, y desarrolladores que necesitan un rastreador ético que respete robots.txt.',
                'cuerpo'      => '<h2>Qué hace</h2><p>Le dices qué buscas y dónde: «bares» en «Quito», «ferreterías» en «Cuenca». Los negocios salen de OpenStreetMap; los que tienen sitio web se visitan y se califican con un catálogo de señales con peso: si el sitio responde, si se ve en el celular, si tiene forma de contacto, con qué está hecho y hace cuánto no lo actualizan.</p><p>El segundo modo recorre un sitio que tú indicas y lista los documentos que enlaza: PDF, Word, Excel.</p><h2>Cómo está hecha</h2><ul><li>Python, FastAPI, y la librería estándar para la descarga: sin dependencias para rastrear.</li><li>Cada búsqueda es un trabajo en segundo plano con avance consultable.</li><li>Respeta robots.txt, espera entre peticiones al mismo dominio y se identifica con su propio User-Agent.</li><li>Rechaza cualquier dirección que no sea pública: comparte servidor con otros servicios y no puede leer la red interna.</li></ul><h2>Lo que sabemos que le falta</h2><p>OpenStreetMap no tiene registrado el sitio web de la mayoría de negocios: en Quito, de 208 bares, solo 2 lo tienen. La araña lo dice como señal en vez de inventarlo.</p>',
                'stack'       => ['Python', 'FastAPI', 'OpenStreetMap', 'Overpass API'],
                'herramienta' => 'arana',
                'servicio_url'=> 'https://arana.srv1666598.hstgr.cloud',
                'seo_titulo'  => 'Araña de búsqueda: negocios por rubro y ciudad, calificados',
                'seo_descripcion' => 'Busca bares en Quito o ferreterías en Cuenca, visita sus sitios y califícalos con señales reales. Un microservicio de MashaCorp que puedes usar aquí.',
            ],
            [
                'titulo'      => 'Formulación y costeo de recetas',
                'slug'        => 'formulacion',
                'resumen'     => 'Escribe una receta con sus ingredientes, mermas y costos de compra, y obtén el costo por unidad y las cantidades para producir lo que necesites.',
                'para_quien'  => 'Producción de alimentos, bebidas, cosmética o cualquier proceso con receta, y desarrolladores que necesitan un motor de costeo que convierta unidades sin equivocarse.',
                'cuerpo'      => '<h2>Qué hace</h2><p>Cada ingrediente se pide en una unidad (700 ml) y se compra en otra (USD 4,50 el litro). El servicio convierte, aplica la merma —para obtener 2 kg útiles con 10 % de pérdida hay que partir de 2,22 kg— y calcula el costo del lote y de cada unidad. Después escala la receta a la cantidad que quieras producir.</p><h2>Cómo está hecho</h2><ul><li>Python y FastAPI, con aritmética decimal: el costo no se redondea a mitad del cálculo.</li><li>Es el mismo motor que usa el ERP de MashaCorp para costear lo que sale de bodega. Allá explota recetas anidadas hasta ocho niveles y detecta las que se contienen a sí mismas.</li><li>No convierte entre masa y volumen: haría falta la densidad del producto, y adivinarla es peor que no responder.</li></ul>',
                'stack'       => ['Python', 'FastAPI', 'Decimal'],
                'herramienta' => 'formulacion',
                'servicio_url'=> 'https://formulacion.srv1666598.hstgr.cloud',
                'seo_titulo'  => 'Costeo de recetas con merma y conversión de unidades',
                'seo_descripcion' => 'Escribe tu receta, sus mermas y lo que pagas por cada insumo, y obtén el costo por unidad y las cantidades para producir. Microservicio de MashaCorp.',
            ],
        ];

        foreach ($investigaciones as $i => $dato) {
            DB::table('sitio_investigaciones')->insert([
                ...$dato,
                'stack'      => json_encode($dato['stack'], JSON_UNESCAPED_UNICODE),
                'empresa_id' => $empresaId,
                'estado'     => 'en_vivo',
                'sort_order' => $i,
                'activo'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        \App\Support\SitioPropio::renovarSello(config('sitio.empresa_slug'), publicar: false);
    }

    public function down(): void
    {
        Schema::dropIfExists('sitio_investigaciones');
    }
};
