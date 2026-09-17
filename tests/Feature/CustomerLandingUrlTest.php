<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Empresa;
use Tests\TestCase;

/**
 * La URL pública de la landing de un punto de venta.
 *
 * Es la que se codifica en el QR y se comparte por link. El bug que estas pruebas
 * fijan: antes generaba /clientes/{slug}, sin la empresa, y como el slug del
 * customer es único por empresa (no global), la landing quedaba irresoluble y el
 * QR llevaba a una página que no existía.
 */
class CustomerLandingUrlTest extends TestCase
{
    /** Arma un customer con su empresa en memoria, sin tocar la base. */
    private function puntoDeVenta(?string $slug, string $empresaSlug = 'rivet-ecuador-sas'): Customer
    {
        $empresa = new Empresa(['slug' => $empresaSlug]);

        $customer = new Customer();
        $customer->slug = $slug;
        $customer->setRelation('empresa', $empresa);

        return $customer;
    }

    public function test_la_url_incluye_empresa_y_cliente(): void
    {
        config(['app.frontend_url' => 'https://tienda.mashaec.net']);

        $url = $this->puntoDeVenta('licoreria-247')->landingUrl();

        $this->assertSame('https://tienda.mashaec.net/rivet-ecuador-sas/licoreria-247', $url);
    }

    public function test_no_genera_la_ruta_vieja_sin_empresa(): void
    {
        config(['app.frontend_url' => 'https://tienda.mashaec.net']);

        $url = $this->puntoDeVenta('licoreria-247')->landingUrl();

        // La ruta /clientes/{slug} era la que rompía el QR: no debe volver a salir.
        $this->assertStringNotContainsString('/clientes/', $url);
    }

    public function test_sin_slug_devuelve_solo_la_base(): void
    {
        config(['app.frontend_url' => 'https://tienda.mashaec.net']);

        $url = $this->puntoDeVenta(null)->landingUrl();

        $this->assertSame('https://tienda.mashaec.net', $url);
    }

    public function test_recorta_la_barra_final_de_la_base(): void
    {
        config(['app.frontend_url' => 'https://tienda.mashaec.net/']);

        $url = $this->puntoDeVenta('licoreria-247')->landingUrl();

        $this->assertSame('https://tienda.mashaec.net/rivet-ecuador-sas/licoreria-247', $url);
    }
}
