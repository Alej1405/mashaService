<?php

namespace Tests\Feature;

use App\Filament\Auth\LoginUsuarios;
use App\Mail\SolicitudAccesoMail;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Crear una cuenta" en el login de usuarios.
 *
 * El ERP no tiene registro abierto: esto solo manda el aviso. Lo que se prueba
 * es que el correo salga con los datos, que la trampa para robots no lo envíe,
 * y que nadie pueda usarlo para inundar el buzón.
 *
 * Corre contra Postgres, no contra el SQLite de phpunit.xml:
 *   DB_CONNECTION=pgsql DB_DATABASE=erp_mashaec_test DB_USERNAME=erp_user \
 *   DB_PASSWORD=… php artisan test --filter=SolicitudAccesoTest
 */
class SolicitudAccesoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        RateLimiter::clear('solicitud-acceso:127.0.0.1');
        Filament::setCurrentPanel(Filament::getPanel('operaciones'));
    }

    public function test_la_pantalla_de_ingreso_carga(): void
    {
        $this->get('/operaciones/login')
            ->assertOk()
            ->assertSee('Portal de Usuarios')
            ->assertSee('Crear una cuenta');
    }

    public function test_la_solicitud_manda_el_correo_y_confirma(): void
    {
        Livewire::test(LoginUsuarios::class)
            ->fillForm([
                'nombre'   => 'Pablo Revilla',
                'empresa'  => 'Rivet Ecuador',
                'correo'   => 'pablo@rivet-ec.com',
                'telefono' => '0999999999',
                'mensaje'  => 'Quiero inventario y facturación.',
            ], 'solicitudForm')
            ->call('solicitarAcceso')
            ->assertSet('solicitudEnviada', true);

        Mail::assertSent(SolicitudAccesoMail::class, function (SolicitudAccesoMail $correo) {
            return $correo->empresa === 'Rivet Ecuador'
                && $correo->correo === 'pablo@rivet-ec.com'
                && str_contains($correo->buildHtml(), 'Pablo Revilla');
        });
    }

    public function test_sin_los_datos_obligatorios_no_se_envia(): void
    {
        Livewire::test(LoginUsuarios::class)
            ->fillForm(['nombre' => 'Pablo Revilla'], 'solicitudForm')
            ->call('solicitarAcceso')
            ->assertHasFormErrors(['empresa', 'correo'], 'solicitudForm')
            ->assertSet('solicitudEnviada', false);

        Mail::assertNothingSent();
    }

    public function test_la_trampa_para_robots_no_manda_correo(): void
    {
        Livewire::test(LoginUsuarios::class)
            ->fillForm([
                'nombre'    => 'Robot',
                'empresa'   => 'Robot SA',
                'correo'    => 'robot@spam.com',
                'sitio_web' => 'https://spam.example',
            ], 'solicitudForm')
            ->call('solicitarAcceso')
            // Al robot se le responde lo mismo: no se le avisa que fue detectado.
            ->assertSet('solicitudEnviada', true);

        Mail::assertNothingSent();
    }

    public function test_a_la_cuarta_solicitud_seguida_se_corta(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            Livewire::test(LoginUsuarios::class)
                ->fillForm([
                    'nombre'  => 'Pablo ' . $i,
                    'empresa' => 'Rivet',
                    'correo'  => "pablo{$i}@rivet-ec.com",
                ], 'solicitudForm')
                ->call('solicitarAcceso');
        }

        Livewire::test(LoginUsuarios::class)
            ->fillForm(['nombre' => 'Pablo 4', 'empresa' => 'Rivet', 'correo' => 'pablo4@rivet-ec.com'], 'solicitudForm')
            ->call('solicitarAcceso')
            ->assertHasErrors('datosSolicitud.correo');

        Mail::assertSentCount(3);
    }
}
