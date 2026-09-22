<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * El QR de cada producto.
 *
 * Lo que se imprime y se pega en la gaveta. Dos cosas importan: que la cámara
 * pueda abrirlo sin app intermedia, y que el token identifique el producto sin
 * autorizar a verlo.
 *
 *   DB_CONNECTION=pgsql DB_DATABASE=erp_mashaec_test DB_USERNAME=erp_user \
 *   DB_PASSWORD=… php artisan test --filter=QrInventarioTest
 */
class QrInventarioTest extends TestCase
{
    use DatabaseTransactions;

    private Empresa $empresa;
    private InventoryItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = Empresa::create([
            'name' => 'Rivet QR', 'slug' => 'rivet-qr-' . uniqid(),
            'email' => 'qr@rivet.ec', 'activo' => true,
        ]);

        $this->item = InventoryItem::withoutGlobalScopes()->create([
            'empresa_id' => $this->empresa->id,
            'codigo' => 'MP-QR1', 'nombre' => 'Ácido cítrico', 'type' => 'materia_prima',
            'stock_actual' => 8, 'stock_minimo' => 25, 'costo_promedio' => 4.4364,
            'saldo_valorado' => 35.49, 'activo' => true,
        ]);
    }

    private function usuarioDe(Empresa $empresa): User
    {
        return User::create([
            'name' => 'Bodeguero', 'email' => 'bodega-' . uniqid() . '@mashaec.net',
            'password' => bcrypt('secreto'), 'empresa_id' => $empresa->id,
        ]);
    }

    public function test_cada_producto_nace_con_su_token(): void
    {
        $this->assertNotEmpty($this->item->qr_token);
        $this->assertSame(10, strlen($this->item->qr_token));
    }

    public function test_el_qr_lleva_una_url_para_que_la_camara_la_abra_sola(): void
    {
        $url = $this->item->urlQr();

        $this->assertStringContainsString('/i/' . $this->item->qr_token, $url);
        $this->assertStringStartsWith('http', $url, 'debe ser absoluta o la cámara no la abre');
    }

    public function test_genera_el_svg_para_imprimir(): void
    {
        $svg = $this->item->qrSvg(96);

        $this->assertStringContainsString('<svg', $svg);
        $this->assertGreaterThan(500, strlen($svg));
    }

    public function test_escanear_sin_sesion_manda_al_login_y_recuerda_a_donde_iba(): void
    {
        $respuesta = $this->get('/i/' . $this->item->qr_token);

        $respuesta->assertStatus(302);
        $this->assertStringContainsString('/i/' . $this->item->qr_token, session('url.intended'));
    }

    public function test_quien_tiene_sesion_de_la_empresa_ve_la_ficha(): void
    {
        $this->actingAs($this->usuarioDe($this->empresa))
            ->get('/i/' . $this->item->qr_token)
            ->assertOk()
            ->assertSee('Ácido cítrico')
            ->assertSee('Bajo el mínimo');
    }

    public function test_el_token_identifica_pero_no_autoriza(): void
    {
        $otra = Empresa::create([
            'name' => 'Otra', 'slug' => 'otra-' . uniqid(),
            'email' => 'otra@x.ec', 'activo' => true,
        ]);

        $this->actingAs($this->usuarioDe($otra))
            ->get('/i/' . $this->item->qr_token)
            ->assertStatus(403);
    }

    public function test_un_token_inventado_no_existe(): void
    {
        $this->actingAs($this->usuarioDe($this->empresa))
            ->get('/i/noexisteee')
            ->assertStatus(404);
    }
}
