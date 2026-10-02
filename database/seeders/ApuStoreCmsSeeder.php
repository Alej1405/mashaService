<?php

namespace Database\Seeders;

use App\Models\CmsAbout;
use App\Models\CmsContact;
use App\Models\CmsHero;
use App\Models\CmsService;
use App\Models\Empresa;
use Illuminate\Database\Seeder;

/**
 * Contenido de apustore-ec.com, tomado del frontend actual (repo apustore-fron).
 *
 * Uso:
 *   php artisan db:seed --class=ApuStoreCmsSeeder
 *
 * Usa updateOrCreate: se puede correr varias veces sin duplicar. El CmsObserver
 * limpia la caché del CMS al guardar, así que el sitio lo ve de inmediato.
 *
 * Las imágenes no se cargan aquí: se suben desde el panel CMS.
 */
class ApuStoreCmsSeeder extends Seeder
{
    private const EMPRESA_SLUG = 'apustore';

    public function run(): void
    {
        $empresa = Empresa::where('slug', self::EMPRESA_SLUG)->first();

        if (! $empresa) {
            $this->command->error("No existe la empresa con slug '" . self::EMPRESA_SLUG . "'.");
            return;
        }

        $this->command->info("Poblando CMS para: {$empresa->name} (ID: {$empresa->id})");

        $this->seedHero($empresa->id);
        $this->seedAbout($empresa->id);
        $this->seedServices($empresa->id);
        $this->seedContact($empresa->id);

        $this->command->info('✓ CMS de ApuStore cargado.');
        $this->command->warn('⚠ Falta subir desde el panel: imagen del hero, imagen de nosotros e imagen del servicio.');
    }

    private function seedHero(int $empresaId): void
    {
        CmsHero::withoutGlobalScopes()->updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'titulo'      => 'Viste Natural',
                'subtitulo'   => 'Tienda Online de Ropa Ecológica y Sustentable',
                'descripcion' => 'Viste con estilo mientras cuidas del planeta. ¡Descubre la moda sostenible que conecta contigo y con la naturaleza!',
                'cta_texto'   => 'Compra aquí',
                'cta_url'     => '/colecciones',
                'imagen'      => null,
                'activo'      => true,
            ]
        );

        $this->command->line('  → Hero');
    }

    private function seedAbout(int $empresaId): void
    {
        CmsAbout::withoutGlobalScopes()->updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'titulo'      => 'Nosotros',
                'descripcion' => 'Ofrecer y ofertar a nuestros clientes productos de calidad, con costos competitivos en el mercado, acordes a su necesidad y exigencia, productos que están acordes a su estilo de vivir la vida.',
                // Misión, visión y objetivos: el front los muestra en este orden.
                'por_que_nosotros' => [
                    'Ofrecer y ofertar a nuestros clientes productos de calidad, con costos competitivos en el mercado, acordes a su necesidad y exigencia, productos que están acordes a su estilo de vivir la vida.',
                    'Ser un negocio líder y reconocido en la venta de diseños exclusivos en prendas de estampado, generando un servicio de calidad y excelencia a nuestros clientes, siendo altamente competitivos en el mercado nacional.',
                    'Ofrecer una amplia variedad de productos de moda exclusivos, de calidad y a precios accesibles.',
                ],
                'numeros'         => [],
                'caracteristicas' => ['Moda sostenible', 'Diseños exclusivos', 'Serigrafía', 'Estampado DTF'],
                'imagen'          => null,
                'activo'          => true,
            ]
        );

        $this->command->line('  → Nosotros');
    }

    private function seedServices(int $empresaId): void
    {
        CmsService::withoutGlobalScopes()->updateOrCreate(
            ['empresa_id' => $empresaId, 'titulo' => 'Serigrafía'],
            [
                'descripcion'     => 'Personalizamos todo tipo de prenda con el sutil y único arte de la serigrafía.',
                'caracteristicas' => ['Diseños personalizados', 'Todo tipo de prenda'],
                'icono'           => null,
                'imagen'          => null,
                'sort_order'      => 1,
                'activo'          => true,
            ]
        );

        $this->command->line('  → Servicios');
    }

    private function seedContact(int $empresaId): void
    {
        CmsContact::withoutGlobalScopes()->updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'direccion'  => 'Uyumbicho, Ecuador',
                'telefono'   => '0960154992',
                'email'      => 'ventas@apustore-ec.com',
                'whatsapp'   => '0960154992',
                'mapa_embed' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.7282438550274!2d-78.5245563247857!3d-0.38500609961120663!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x91d5a460133fffff%3A0xb3877f549365d77d!2sparque%20central%20Uyumbicho!5e0!3m2!1ses!2sec!4v1737078304179!5m2!1ses!2sec',
                'facebook'   => 'https://www.facebook.com/profile.php?id=100089894168343',
                'instagram'  => 'https://www.instagram.com/apu_store222/',
                'linkedin'   => null,
                'youtube'    => null,
                'tiktok'     => null,
                'activo'     => true,
            ]
        );

        $this->command->line('  → Contacto');
    }
}
