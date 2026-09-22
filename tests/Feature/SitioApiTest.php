<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\SitioCaso;
use App\Models\SitioMensaje;
use App\Models\SitioNavegacion;
use App\Models\SitioPagina;
use App\Support\SitioPropio;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * API del sitio propio.
 *
 * Corre contra Postgres, no contra el SQLite en memoria de phpunit.xml: el
 * historial de migraciones del ERP no reproduce desde cero (el proyecto nació
 * en MySQL y pasó a Postgres con pgloader). La base de pruebas se arma una vez
 * clonando el esquema real y cada prueba se revierte en una transacción:
 *
 *   createdb -U mashaec -O erp_user erp_mashaec_test
 *   pg_dump -U erp_user --schema-only --no-owner --no-privileges erp_mashaec \
 *     | psql -U erp_user -d erp_mashaec_test
 *
 *   DB_CONNECTION=pgsql DB_DATABASE=erp_mashaec_test DB_USERNAME=erp_user \
 *   DB_PASSWORD=… php artisan test --filter=SitioApiTest
 *
 * Fija tres cosas que el front en Svelte da por hechas: que una ruta se
 * resuelve en una sola petición, que revalidar cuesta un 304 mientras nadie
 * edite, y que un caso sin autorización del cliente no sale nunca.
 */
class SitioApiTest extends TestCase
{
    use DatabaseTransactions;

    private Empresa $empresa;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::create([
            'name'   => 'MashaCorp',
            'slug'   => SitioPropio::slug(),
            'email'  => 'pruebas@mashacorp.ec',
            'activo' => true,
        ]);

        $this->token = $this->empresa->createToken('sitio')->plainTextToken;
    }

    private function encabezados(array $extra = []): array
    {
        return array_merge(['Authorization' => "Bearer {$this->token}"], $extra);
    }

    private function url(string $ruta): string
    {
        return '/api/sitio/' . SitioPropio::slug() . $ruta;
    }

    public function test_sin_token_responde_401(): void
    {
        $this->getJson($this->url('/layout'))->assertStatus(401);
    }

    public function test_el_layout_trae_la_navegacion_separada_por_ubicacion(): void
    {
        SitioNavegacion::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id,
            'etiqueta'   => 'Desarrollo',
            'ruta'       => '/desarrollo',
            'ubicacion'  => 'superior',
        ]);

        SitioNavegacion::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id,
            'etiqueta'   => 'Inicio',
            'ruta'       => '/',
            'ubicacion'  => 'inferior',
            'icono'      => 'casa',
        ]);

        $this->getJson($this->url('/layout'), $this->encabezados())
            ->assertOk()
            ->assertJsonPath('empresa.slug', SitioPropio::slug())
            ->assertJsonCount(1, 'navegacion.superior')
            ->assertJsonPath('navegacion.inferior.0.icono', 'casa');
    }

    public function test_una_pagina_se_resuelve_en_una_sola_peticion(): void
    {
        SitioPagina::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id,
            'slug'       => 'desarrollo',
            'titulo'     => 'Desarrollo',
            'activo'     => true,
        ]);

        $this->getJson($this->url('/paginas/desarrollo'), $this->encabezados())
            ->assertOk()
            ->assertJsonPath('pagina.titulo', 'Desarrollo')
            ->assertJsonStructure(['pagina', 'casos', 'planes', 'articulos', 'seo']);
    }

    public function test_una_pagina_que_no_existe_responde_404(): void
    {
        $this->getJson($this->url('/paginas/inventada'), $this->encabezados())->assertStatus(404);
    }

    public function test_revalidar_sin_cambios_responde_304(): void
    {
        $primera = $this->getJson($this->url('/layout'), $this->encabezados())->assertOk();
        $etag    = $primera->headers->get('ETag');

        $this->assertNotNull($etag, 'La respuesta debe traer ETag.');

        $this->getJson($this->url('/layout'), $this->encabezados(['If-None-Match' => $etag]))
            ->assertStatus(304);
    }

    public function test_editar_contenido_cambia_el_etag(): void
    {
        $etagInicial = $this->getJson($this->url('/layout'), $this->encabezados())->headers->get('ETag');

        SitioNavegacion::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id,
            'etiqueta'   => 'Laboratorio',
            'ruta'       => '/laboratorio',
            'ubicacion'  => 'superior',
        ]);

        $etagNuevo = $this->getJson($this->url('/layout'), $this->encabezados())->headers->get('ETag');

        $this->assertNotSame($etagInicial, $etagNuevo);
    }

    public function test_un_caso_sin_autorizacion_del_cliente_no_sale(): void
    {
        SitioCaso::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id,
            'titulo'     => 'Caso reservado',
            'slug'       => 'caso-reservado',
            'servicio'   => 'desarrollo',
            'publicable' => false,
            'activo'     => true,
        ]);

        $this->getJson($this->url('/casos/caso-reservado'), $this->encabezados())->assertStatus(404);
    }

    public function test_el_formulario_de_un_campo_guarda_el_mensaje(): void
    {
        $this->postJson($this->url('/mensajes'), [
            'contacto' => 'pablo@ejemplo.ec',
            'mensaje'  => 'Quiero una web.',
            'origen'   => '/desarrollo',
        ], $this->encabezados())->assertStatus(201);

        $mensaje = SitioMensaje::withoutGlobalScopes()->first();

        $this->assertSame('pablo@ejemplo.ec', $mensaje->contacto);
        $this->assertNotNull($mensaje->ip_hash, 'Se guarda el hash de la IP, nunca la IP.');
    }

    public function test_el_contacto_debe_ser_correo_o_telefono(): void
    {
        $this->postJson($this->url('/mensajes'), ['contacto' => 'hola qué tal'], $this->encabezados())
            ->assertStatus(422)
            ->assertJsonValidationErrors('contacto');
    }

    public function test_la_trampa_para_robots_rechaza_el_envio(): void
    {
        $this->postJson($this->url('/mensajes'), [
            'contacto'  => 'pablo@ejemplo.ec',
            'sitio_web' => 'http://spam.example',
        ], $this->encabezados())->assertStatus(422);

        $this->assertSame(0, SitioMensaje::withoutGlobalScopes()->count());
    }
}
