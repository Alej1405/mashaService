<?php

namespace Database\Seeders;

use App\Models\Empresa;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * El contenido del sitio propio: mashaec.net.
 *
 * Lo que este seeder escribe es lo que el front en Svelte lee por
 * `/api/sitio/masha-corp-sas/…`. Después se edita en el panel de administración
 * y nunca más hace falta correrlo; existe para que el sitio no arranque vacío y
 * para poder reconstruirlo si alguien borra de más.
 *
 * Los tres casos son clientes reales con su trabajo real. Ninguna cifra que no
 * podamos sostener: si un número no está verificado, no se publica.
 *
 *   php artisan db:seed --class=SitioPropioSeeder
 */
class SitioPropioSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::withoutGlobalScopes()
            ->where('slug', config('sitio.empresa_slug'))
            ->first();

        if (! $empresa) {
            $this->command->error('No existe la empresa del sitio propio.');

            return;
        }

        $id = $empresa->id;

        $this->hero($id);
        $this->casos($id);
        $this->articulos($id);
        $this->planes($id);

        $this->command->info('Sitio propio: contenido cargado.');
    }

    /** El titular que el cliente ve primero. */
    private function hero(int $empresaId): void
    {
        DB::table('cms_heroes')->updateOrInsert(
            ['empresa_id' => $empresaId],
            [
                'titulo' => "El sitio es tuyo.\nEl contenido también.",
                'subtitulo' => 'Sitios web, ERP de contenido y fotografía. Quito, Ecuador.',
                'descripcion' => 'Hacemos el sitio, producimos las fotos que van dentro y lo entregamos '
                    . 'con un panel donde cambias cualquier texto, cualquier foto y cualquier enlace. '
                    . 'Sin llamarnos.',
                'cta_texto' => 'Abrir el panel de prueba',
                'cta_url' => '/erp',
                'activo' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    /**
     * El portafolio.
     *
     * Cada caso cuenta qué necesitaba el cliente, qué se hizo y qué administra
     * él hoy. Eso último es el argumento de venta: el panel no es una promesa,
     * es algo que ya está funcionando en tres empresas.
     */
    private function casos(int $empresaId): void
    {
        $casos = [
            [
                'slug' => 'link-cargo',
                'titulo' => 'El sitio que habla con el ERP',
                'cliente' => 'Link Cargo Ecuador',
                'sector' => 'Comercio exterior y logística',
                'servicio' => 'Sitio · Panel · ERP',
                'resumen' => 'Una empresa de carga con un ERP propio que ya funcionaba y un sitio '
                    . 'que no hablaba con él: los servicios se actualizaban a mano en dos lugares.',
                'problema' => 'El equipo comercial cambiaba un servicio en el sistema interno y el '
                    . 'sitio seguía mostrando lo viejo durante semanas. Cada corrección pasaba por '
                    . 'nosotros y costaba tiempo de desarrollo.',
                'solucion' => 'Conectamos el sitio al ERP por API: los siete servicios, las tarifas, '
                    . 'las zonas de cobertura y las noticias salen del mismo sistema que mueve la '
                    . 'operación. Una fuente, dos caras. El despliegue compila y publica solo.',
                'resultado' => 'El contenido del sitio lo administra Link Cargo sin escribirnos. La '
                    . 'carga inicial bajó de 205 KB a 95 KB y la portada se entrega prerenderizada, '
                    . 'que es como la leen los buscadores.',
                'metricas' => json_encode([
                    ['valor' => '7', 'etiqueta' => 'servicios administrables'],
                    ['valor' => '95 KB', 'etiqueta' => 'carga inicial'],
                    ['valor' => '0', 'etiqueta' => 'llamadas para cambiar un texto'],
                ]),
                'enlace_sitio' => 'https://linkcargoecuador.com',
                'enlace_repo' => null,   // repositorio privado del cliente
                'destacado' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'rivet-pinteno',
                'titulo' => 'Del páramo a la botella, y a la contabilidad',
                'cliente' => 'Rivet Ecuador S.A.S.',
                'sector' => 'Licor artesanal · Píntag',
                'servicio' => 'ERP · Fotografía · Sitio',
                'resumen' => 'Rivet produce el licor Pinteño con mortiño y sunfo que compra directo '
                    . 'a familias del páramo. Necesitaba controlar el costo real de cada botella y '
                    . 'cumplir con el SRI sin depender de hojas de cálculo.',
                'problema' => 'La materia prima se compra a productores pequeños, la producción pasa '
                    . 'por maceración y embotellado, y cada mes hay que declarar. Nada de eso estaba '
                    . 'registrado: el costo de una botella era una estimación.',
                'solucion' => 'ERP a medida con inventario valorado al promedio ponderado, fórmulas '
                    . 'de producción que explotan hasta la materia prima procesada, contabilidad con '
                    . 'asiento automático en cada movimiento, y generación del formulario 104 y del '
                    . 'anexo transaccional. Más la fotografía de producto para el catálogo.',
                'resultado' => 'Cada botella tiene su costo real trazado hasta el kilo de mortiño. '
                    . 'Las declaraciones salen del mismo sistema que registra las compras, así que '
                    . 'el balance y el formulario dicen lo mismo.',
                'metricas' => json_encode([
                    ['valor' => '104 y ATS', 'etiqueta' => 'se generan del sistema'],
                    ['valor' => 'Kardex', 'etiqueta' => 'valorado por lote'],
                ]),
                'enlace_sitio' => null,
                'enlace_repo' => null,   // se publica cuando el repo esté abierto
                'destacado' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'zarku',
                'titulo' => 'Un catálogo que su dueña cambia sola',
                'cliente' => 'Zarku Ecuador',
                'sector' => 'Comercio en línea',
                'servicio' => 'Tienda · Panel',
                'resumen' => 'Una tienda con catálogo propio donde el precio, la foto y la '
                    . 'disponibilidad cambian sin tocar el código y sin esperar a nadie.',
                'problema' => 'Cada cambio de precio o de foto dependía de un tercero. Un producto '
                    . 'agotado seguía publicado el fin de semana entero.',
                'solucion' => 'Catálogo conectado al panel: producto, categoría, precio y foto se '
                    . 'editan desde el celular. Los pedidos entran al mismo sistema y quedan con su '
                    . 'trazabilidad.',
                'resultado' => 'El catálogo se mantiene al día sin intermediarios. Publicar un '
                    . 'producto nuevo toma minutos, no días.',
                'metricas' => json_encode([
                    ['valor' => '21', 'etiqueta' => 'productos publicados'],
                    ['valor' => 'Minutos', 'etiqueta' => 'para publicar uno nuevo'],
                ]),
                'enlace_sitio' => 'https://zarku.ec',
                'enlace_repo' => null,
                'destacado' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($casos as $caso) {
            DB::table('sitio_casos')->updateOrInsert(
                ['empresa_id' => $empresaId, 'slug' => $caso['slug']],
                $caso + [
                    'empresa_id' => $empresaId,
                    'publicable' => true,
                    'activo' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    /**
     * El laboratorio.
     *
     * Lo que investigamos mientras trabajamos. Aquí no se vende nada: es lo que
     * hace que alguien nos encuentre buscando un problema, no un proveedor.
     */
    private function articulos(int $empresaId): void
    {
        $articulos = [
            [
                'slug' => 'el-clic-que-ya-no-llega',
                'titulo' => 'El clic que ya no llega',
                'resumen' => 'Cuando aparece un resumen de IA en Google, el clic a un resultado '
                    . 'tradicional cae del 15 % al 8 %. Qué significa eso para un sitio corporativo '
                    . 'y por qué el argumento de venta de una web ya no puede ser el tráfico.',
                'serie' => 'Buscadores',
                'publicado_en' => '2026-09-12',
            ],
            [
                'slug' => 'arana-de-prospeccion',
                'titulo' => 'Una araña de prospección en 300 líneas y cero dependencias',
                'resumen' => 'Por qué la librería estándar de Python alcanza para rastrear la web, '
                    . 'y qué se gana en mantenimiento al no instalar nada.',
                'serie' => 'Herramientas',
                'publicado_en' => '2026-09-05',
            ],
            [
                'slug' => 'el-boton-que-cuenta',
                'titulo' => 'El botón que cuenta lo que está haciendo',
                'resumen' => 'Cinco estados en vez de tres, y por qué el paso a «cargando» no debe '
                    . 'llevar transición: cualquier demora ahí se lee como que no registró el clic.',
                'serie' => 'Interfaz',
                'publicado_en' => '2026-08-28',
            ],
            [
                'slug' => 'que-la-ia-te-cite',
                'titulo' => 'Que la IA te cite: contenido estructurado y editable',
                'resumen' => 'Si el tráfico informativo se encoge, el valor se mueve a ser la fuente '
                    . 'que el modelo nombra. Y para que te nombre, el contenido tiene que estar '
                    . 'estructurado y, sobre todo, actualizado.',
                'serie' => 'Buscadores',
                'publicado_en' => '2026-08-20',
            ],
            [
                'slug' => 'certificados-tls-macos',
                'titulo' => 'Certificados TLS en macOS: por qué tu script no abre ningún sitio',
                'resumen' => 'El fallo que rompe cualquier cliente HTTP en Python instalado desde '
                    . 'python.org, y cómo resolverlo sin tocar el sistema.',
                'serie' => 'Herramientas',
                'publicado_en' => '2026-08-14',
            ],
        ];

        foreach ($articulos as $articulo) {
            DB::table('cms_posts')->updateOrInsert(
                ['empresa_id' => $empresaId, 'slug' => $articulo['slug']],
                $articulo + [
                    'empresa_id' => $empresaId,
                    'contenido' => '',   // el cuerpo se escribe en el panel
                    'activo' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    /** Los precios de cada sección, que en el sitio se dicen antes de la reunión. */
    private function planes(int $empresaId): void
    {
        $planes = [
            ['desarrollo', 'Sitio con panel', 1800, 'Hasta 6 pantallas. De 4 a 6 semanas.', 'Diseño en Figma de todas las pantallas|Los tres tamaños de pantalla|Panel de contenido conectado|Despliegue y dominio|Capacitación de uso', true, 1],
            ['desarrollo', 'Conectado a tu sistema', 3200, 'Consume tu ERP o tu base. De 6 a 10 semanas.', 'Todo lo del plan anterior|Integración por API con tu sistema|Prerenderizado para buscadores', false, 2],
            ['desarrollo', 'Acompañamiento', 90, 'Panel, alojamiento, respaldos y funciones nuevas.', 'Alojamiento y dominio|Respaldos|Funciones nuevas que vayamos liberando', false, 3],
            ['fotografia', 'Sesión de producto', 280, 'Hasta 10 piezas. Entrega en 5 días.', 'Fondo controlado o ambientado|Retoque incluido|Entrega lista para el sitio', true, 1],
            ['fotografia', 'Sesión de marca', 450, 'Medio día en tu espacio. Entrega en 7 días.', 'El espacio, el equipo y el proceso|Selección y retoque', false, 2],
            ['fotografia', 'Evento corporativo', 350, 'Hasta 4 horas. Selección en 48 horas.', 'Solo fotografía|Selección en 48 horas', false, 3],
        ];

        foreach ($planes as [$pagina, $nombre, $precio, $nota, $incluye, $destacado, $orden]) {
            DB::table('sitio_planes')->updateOrInsert(
                ['empresa_id' => $empresaId, 'pagina_slug' => $pagina, 'nombre' => $nombre],
                [
                    'empresa_id' => $empresaId,
                    'pagina_slug' => $pagina,
                    'nombre' => $nombre,
                    'descripcion' => null,
                    'precio_desde' => $precio,
                    'moneda' => 'USD',
                    'periodicidad' => $nombre === 'Acompañamiento' ? 'mensual' : 'proyecto',
                    'incluye' => json_encode(explode('|', $incluye)),
                    'nota' => $nota,
                    'destacado' => $destacado,
                    'sort_order' => $orden,
                    'activo' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }
}
