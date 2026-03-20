<?php

namespace Database\Seeders;

use App\Models\CmsAbout;
use App\Models\CmsContact;
use App\Models\CmsHero;
use App\Models\CmsService;
use App\Models\Empresa;
use Illuminate\Database\Seeder;

/**
 * Seeder con el contenido real de linkcargoecuador.com
 *
 * Uso:
 *   php artisan db:seed --class=LinkCargoCmsSeeder
 *
 * Por defecto busca la empresa con slug 'link-cargo'.
 * Cámbialo en la constante EMPRESA_SLUG si el slug es diferente.
 *
 * ─────────────────────────────────────────────────────────────────
 * PENDIENTE (completar manualmente desde el panel CMS):
 *   - Imagen de fondo del Hero
 *   - Imágenes de cada servicio
 *   - Google Maps embed (sección Contacto)
 *   - URLs de redes sociales (Facebook, Instagram, LinkedIn, etc.)
 *   - Logos de clientes
 *   - Testimonios de clientes
 *   - Integrantes del equipo con fotos
 *   - Preguntas frecuentes (FAQ)
 * ─────────────────────────────────────────────────────────────────
 */
class LinkCargoCmsSeeder extends Seeder
{
    private const EMPRESA_SLUG = 'link-cargo-ecuador';

    public function run(): void
    {
        $empresa = Empresa::where('slug', self::EMPRESA_SLUG)->first();

        if (! $empresa) {
            $this->command->error(
                "No se encontró ninguna empresa con slug '" . self::EMPRESA_SLUG . "'.\n" .
                "Edita la constante EMPRESA_SLUG en este seeder con el slug correcto."
            );
            return;
        }

        $this->command->info("Poblando CMS para: {$empresa->name} (ID: {$empresa->id})");

        $this->seedHero($empresa->id);
        $this->seedAbout($empresa->id);
        $this->seedServices($empresa->id);
        $this->seedContact($empresa->id);

        $this->command->info('✓ CMS de Link Cargo cargado correctamente.');
        $this->command->warn('⚠ Recuerda completar manualmente: imágenes, mapa, redes sociales, equipo, clientes, testimonios y FAQ.');
    }

    // ── Hero ────────────────────────────────────────────────────────────────

    private function seedHero(int $empresaId): void
    {
        CmsHero::updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'titulo'      => 'Potenciamos su Negocio hacia el Liderazgo Global',
                'subtitulo'   => 'Link Cargo Global Connect',
                'descripcion' => 'Soluciones logísticas innovadoras para importación, exportación y gestión aduanera con cobertura técnica en los principales mercados mundiales.',
                'cta_texto'   => 'Hablar con un Experto',
                'cta_url'     => 'https://wa.me/593980554503',
                'imagen'      => null, // subir manualmente desde el panel
                'activo'      => true,
            ]
        );

        $this->command->line('  → Hero creado');
    }

    // ── Nosotros ────────────────────────────────────────────────────────────

    private function seedAbout(int $empresaId): void
    {
        $cuerpo = <<<HTML
<p>En Link Cargo, entendemos que cada carga es única. Por eso, diseñamos soluciones personalizadas que optimizan tus operaciones de comercio internacional, garantizando eficiencia, seguridad y costos competitivos.</p>

<p>Somos una empresa dedicada a la prestación de servicios de comercio exterior y logística internacional. Nuestro principal enfoque está en la eficiencia, agilidad, precisión y calidad de nuestros servicios.</p>

<h2>¿Por qué Link Cargo?</h2>
<ul>
  <li>✔ Tarifas competitivas</li>
  <li>✔ Seguimiento en tiempo real</li>
  <li>✔ Asesoría personalizada</li>
  <li>✔ Amplia red de agentes</li>
  <li>✔ Tecnología de punta</li>
  <li>✔ Cumplimiento normativo</li>
</ul>

<h2>Nuestros números</h2>
<ul>
  <li><strong>12+</strong> años de trayectoria</li>
  <li><strong>800+</strong> corresponsales</li>
  <li><strong>50+</strong> conexiones globales</li>
  <li><strong>10.000+</strong> operaciones exitosas</li>
  <li><strong>98%</strong> índice de satisfacción de clientes</li>
</ul>

<h2>Experiencia Comprobada</h2>
<p>Más de 15 años liderando el comercio exterior en Ecuador.</p>

<h2>Equipo Experto</h2>
<p>Profesionales certificados en logística y comercio internacional.</p>

<h2>Atención 24/7</h2>
<p>Soporte continuo para seguimiento de tus envíos.</p>
HTML;

        CmsAbout::updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'titulo'  => 'Tu socio estratégico en comercio exterior',
                'cuerpo'  => $cuerpo,
                'imagen'  => null, // subir manualmente desde el panel
                'activo'  => true,
            ]
        );

        $this->command->line('  → Nosotros creado');
    }

    // ── Servicios ───────────────────────────────────────────────────────────

    private function seedServices(int $empresaId): void
    {
        // Limpiar servicios previos de esta empresa para evitar duplicados
        \App\Models\CmsService::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->delete();

        $servicios = [
            [
                'titulo'      => 'Transporte Marítimo',
                'descripcion' => 'Fletes marítimos FCL y LCL con cobertura global. Optimizamos costos y tiempos de tránsito.',
                'icono'       => '🚢',
                'sort_order'  => 1,
            ],
            [
                'titulo'      => 'Transporte Aéreo',
                'descripcion' => 'Carga aérea express y consolidada. Ideal para envíos urgentes y mercancías de alto valor.',
                'icono'       => '✈️',
                'sort_order'  => 2,
            ],
            [
                'titulo'      => 'Transporte Terrestre',
                'descripcion' => 'Distribución nacional e internacional. Red de transporte confiable en toda la región.',
                'icono'       => '🚛',
                'sort_order'  => 3,
            ],
            [
                'titulo'      => 'Agenciamiento Aduanero',
                'descripcion' => 'Gestión integral de trámites aduaneros. Clasificación arancelaria y documentación.',
                'icono'       => '📋',
                'sort_order'  => 4,
            ],
            [
                'titulo'      => 'Servicios Especializados',
                'descripcion' => 'Búsqueda de proveedores, internacionalización y capacitaciones en comercio exterior.',
                'icono'       => '🔍',
                'sort_order'  => 5,
            ],
            [
                'titulo'      => 'Seguro de Carga',
                'descripcion' => 'Protección integral para tu mercancía. Cobertura puerta a puerta.',
                'icono'       => '🛡️',
                'sort_order'  => 6,
            ],
        ];

        foreach ($servicios as $servicio) {
            \App\Models\CmsService::create(array_merge($servicio, [
                'empresa_id' => $empresaId,
                'activo'     => true,
            ]));
        }

        $this->command->line('  → 6 servicios creados');
    }

    // ── Contacto ────────────────────────────────────────────────────────────

    private function seedContact(int $empresaId): void
    {
        CmsContact::updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'direccion'  => 'Av. Principal 123, Guayaquil, Ecuador',
                'telefono'   => '+593 4 123 4567',
                'whatsapp'   => '+593 980 554 503',
                'email'      => 'info@linkcargo.ec',
                'mapa_embed' => null, // pegar el iframe de Google Maps manualmente
                // Redes sociales: agregar las URLs manualmente desde el panel
                'facebook'   => null,
                'instagram'  => null,
                'linkedin'   => null,
                'youtube'    => null,
                'tiktok'     => null,
                'activo'     => true,
            ]
        );

        $this->command->line('  → Contacto creado');
    }
}
